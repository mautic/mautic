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
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\ClassMethod;
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
        if (!$param->type instanceof Name) {
            return null;
        }

        $className = $param->type->toString();
        if (!in_array($className, self::GENERIC_ENTITY_EVENT_CLASSES, true)) {
            return null;
        }

        return $className;
    }

    private function hasEntityInstanceofCheck(ClassMethod $classMethod, string $eventVariableName): bool
    {
        $nodeFinder = new NodeFinder();

        // variables holding "$event->getEntity()"
        $entityVariableNames = [];
        foreach ($nodeFinder->findInstanceOf((array) $classMethod->stmts, Assign::class) as $assign) {
            if ($assign->var instanceof Variable && is_string($assign->var->name) && $this->isGetEntityCall($assign->expr, $eventVariableName)) {
                $entityVariableNames[] = $assign->var->name;
            }
        }

        foreach ($nodeFinder->findInstanceOf((array) $classMethod->stmts, Instanceof_::class) as $instanceof) {
            if ($this->isGetEntityCall($instanceof->expr, $eventVariableName)) {
                return true;
            }

            if ($instanceof->expr instanceof Variable && in_array($instanceof->expr->name, $entityVariableNames, true)) {
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
