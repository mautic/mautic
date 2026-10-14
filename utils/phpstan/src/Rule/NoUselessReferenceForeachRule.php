<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Utils\PHPStan\ByReference\ByReferenceParamAnalyzer;

/**
 * A by-reference foreach value that is never written to in the loop body does nothing: mutating an object
 * or calling its methods needs no reference. Only reassigning the value ($v = …, $v[…] = …, ++$v, passing
 * &$v on) relies on the reference. When the body never does that, drop the "&".
 *
 * @implements Rule<Foreach_>
 *
 * @see \Utils\PHPStan\Tests\Rule\NoUselessReferenceForeachRuleTest
 */
final class NoUselessReferenceForeachRule implements Rule
{
    private readonly ByReferenceParamAnalyzer $byReferenceParamAnalyzer;

    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->byReferenceParamAnalyzer = new ByReferenceParamAnalyzer($reflectionProvider);
    }

    public function getNodeType(): string
    {
        return Foreach_::class;
    }

    /**
     * @param Foreach_ $node
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->byRef) {
            return [];
        }

        $valueVar = $node->valueVar;
        if (!$valueVar instanceof Variable || !is_string($valueVar->name)) {
            return [];
        }

        // when the value is itself walked by a nested foreach, the reference is a deliberate "step into the
        // collection" idiom; leave it alone, it turns live the moment the nested loop takes the value by reference
        if ($this->isWalkedByNestedForeach($valueVar->name, $node->stmts)) {
            return [];
        }

        $uselessByName = $this->byReferenceParamAnalyzer->analyse([$valueVar->name], $node->stmts, $scope);
        if (true !== ($uselessByName[$valueVar->name] ?? false)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf(
                'Value "$%s" is referenced by "&" but never written to, so the reference has no effect. Remove the "&".',
                $valueVar->name
            ))
                ->identifier('mautic.noUselessReferenceForeach')
                ->line($valueVar->getStartLine())
                ->build(),
        ];
    }

    /**
     * @param Node\Stmt[] $stmts
     */
    private function isWalkedByNestedForeach(string $valueName, array $stmts): bool
    {
        foreach ((new NodeFinder())->findInstanceOf($stmts, Foreach_::class) as $nestedForeach) {
            $expr = $nestedForeach->expr;
            while ($expr instanceof ArrayDimFetch) {
                $expr = $expr->var;
            }

            if ($expr instanceof Variable && $expr->name === $valueName) {
                return true;
            }
        }

        return false;
    }
}
