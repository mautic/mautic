<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;

/**
 * Reports assertInstanceOf() on a value whose type is already known to be that class,
 * e.g. right after "$translator = $this->getService(TranslatorInterface::class)".
 *
 * @implements Rule<Node\Expr\CallLike>
 *
 * @see \Utils\PHPStan\Tests\Rule\NoUselessAssertInstanceOfRuleTest
 */
final class NoUselessAssertInstanceOfRule implements Rule
{
    public function getNodeType(): string
    {
        return Node\Expr\CallLike::class;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node instanceof MethodCall && !$node instanceof StaticCall) {
            return [];
        }

        if (!$node->name instanceof Identifier || 'assertinstanceof' !== $node->name->toLowerString()) {
            return [];
        }

        if ($node->isFirstClassCallable()) {
            return [];
        }

        $args = $node->getArgs();
        if (count($args) < 2) {
            return [];
        }

        $classExpr = $args[0]->value;
        if (!$classExpr instanceof ClassConstFetch || !$classExpr->class instanceof Name) {
            return [];
        }

        if (!$classExpr->name instanceof Identifier || 'class' !== $classExpr->name->toLowerString()) {
            return [];
        }

        $className = $scope->resolveName($classExpr->class);
        $valueType = $scope->getType($args[1]->value);

        if (!(new ObjectType($className))->isSuperTypeOf($valueType)->yes()) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf(
                'Value is already known to be "%s", remove the useless assertInstanceOf(), or if the type comes only from a @var docblock, remove the @var instead.',
                $className
            ))
                ->identifier('mautic.noUselessAssertInstanceOf')
                ->build(),
        ];
    }
}
