<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use Mautic\CoreBundle\Event\EntityPostDelete;
use Mautic\CoreBundle\Event\EntityPostSave;
use Mautic\CoreBundle\Event\EntityPreDelete;
use Mautic\CoreBundle\Event\EntityPreSave;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Instanceof_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\UnionType;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Generic entity events fire for every entity, so a listener must narrow the entity with instanceof before acting.
 *
 * @implements Rule<ClassMethod>
 *
 * @see \Utils\PHPStan\Tests\Rule\EntityEventListenerMustCheckInstanceofRuleTest
 */
final class EntityEventListenerMustCheckInstanceofRule implements Rule
{
    private const array GENERIC_ENTITY_EVENT_CLASSES = [
        EntityPreSave::class,
        EntityPostSave::class,
        EntityPreDelete::class,
        EntityPostDelete::class,
    ];

    public function getNodeType(): string
    {
        return ClassMethod::class;
    }

    /**
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (null === $node->stmts) {
            return [];
        }

        $ruleErrors = [];
        foreach ($node->params as $param) {
            $eventClass = $this->matchGenericEntityEventClass($param);
            if (null === $eventClass || !$param->var instanceof Variable || !is_string($param->var->name)) {
                continue;
            }

            if ($this->hasEntityInstanceofCheck($node, $param->var->name)) {
                continue;
            }

            $ruleErrors[] = RuleErrorBuilder::message(sprintf(
                'Method "%s()" listens to generic "%s" event that fires for every entity. Check "$%s->getEntity()" with instanceof first.',
                $node->name->toString(),
                $eventClass,
                $param->var->name
            ))
                ->identifier('mautic.entityEventListenerInstanceof')
                ->line($node->getStartLine())
                ->build();
        }

        return $ruleErrors;
    }

    private function matchGenericEntityEventClass(Param $param): ?string
    {
        $type = $param->type instanceof NullableType ? $param->type->type : $param->type;
        $types = $type instanceof UnionType ? $type->types : [$type];

        foreach ($types as $singleType) {
            if ($singleType instanceof Name && in_array($singleType->toString(), self::GENERIC_ENTITY_EVENT_CLASSES, true)) {
                return $singleType->toString();
            }
        }

        return null;
    }

    private function hasEntityInstanceofCheck(ClassMethod $classMethod, string $eventVariableName): bool
    {
        $nodeFinder = new NodeFinder();

        // walk in source order, so only instanceof after "$var = $event->getEntity()" counts
        $nodes = $nodeFinder->find((array) $classMethod->stmts, fn (Node $node): bool => $node instanceof Assign || $node instanceof Instanceof_);

        // variables currently holding "$event->getEntity()"
        $entityVariableNames = [];
        foreach ($nodes as $node) {
            if ($node instanceof Assign) {
                if ($node->var instanceof Variable && is_string($node->var->name)) {
                    $entityVariableNames[$node->var->name] = $this->isGetEntityCall($node->expr, $eventVariableName);
                }

                continue;
            }

            if ($node instanceof Instanceof_ && $this->isGetEntityCall($node->expr, $eventVariableName)) {
                return true;
            }

            if ($node instanceof Instanceof_ && $node->expr instanceof Variable && is_string($node->expr->name) && ($entityVariableNames[$node->expr->name] ?? false)) {
                return true;
            }
        }

        return false;
    }

    private function isGetEntityCall(Expr $expr, string $eventVariableName): bool
    {
        if (!$expr instanceof MethodCall || !$expr->name instanceof Identifier || 'getEntity' !== $expr->name->toString()) {
            return false;
        }

        return $expr->var instanceof Variable && $expr->var->name === $eventVariableName;
    }
}
