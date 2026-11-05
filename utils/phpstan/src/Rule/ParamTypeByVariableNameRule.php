<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * An untyped method param whose name ends with a configured suffix must declare the matching type,
 * e.g. "$parameters" / "$queryParameters" must be "array". A param that already has a native type
 * declaration is left alone.
 *
 * @implements Rule<ClassMethod>
 *
 * @see \Utils\PHPStan\Tests\Rule\ParamTypeByVariableNameRuleTest
 */
final readonly class ParamTypeByVariableNameRule implements Rule
{
    /**
     * @param array<string, string> $variableNameToType map of variable-name suffix to required type
     */
    public function __construct(
        private array $variableNameToType
    ) {
    }

    public function getNodeType(): string
    {
        return ClassMethod::class;
    }

    /**
     * @param ClassMethod $node
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $ruleErrors = [];

        foreach ($node->params as $param) {
            if (!$param->var instanceof Variable || !is_string($param->var->name)) {
                continue;
            }

            // skip params that already have a native type declaration
            if ($param->type instanceof Node) {
                continue;
            }

            $requiredType = $this->matchRequiredType($param->var->name);
            if (null === $requiredType) {
                continue;
            }

            $ruleErrors[] = RuleErrorBuilder::message(sprintf(
                'Param "$%s" of "%s()" must be "%s" based on its name.',
                $param->var->name,
                $node->name->toString(),
                $requiredType
            ))
                ->identifier('mautic.paramTypeByVariableName')
                ->line($param->getStartLine())
                ->build();
        }

        return $ruleErrors;
    }

    private function matchRequiredType(string $variableName): ?string
    {
        $lowerName = strtolower($variableName);

        foreach ($this->variableNameToType as $suffix => $type) {
            if (str_ends_with($lowerName, strtolower($suffix))) {
                return $type;
            }
        }

        return null;
    }
}
