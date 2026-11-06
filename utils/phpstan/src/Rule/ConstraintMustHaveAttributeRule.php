<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Symfony\Component\Validator\Constraint;

/**
 * Every class that extends Symfony Constraint must declare the #[\Attribute] attribute,
 * so it can be used as a PHP attribute on entity properties.
 *
 * @implements Rule<Class_>
 */
final class ConstraintMustHaveAttributeRule implements Rule
{
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
    ) {
    }

    public function getNodeType(): string
    {
        return Class_::class;
    }

    /**
     * @param Class_ $node
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        // an anonymous class carries no name to report, and cannot be used as an attribute
        if (!$node->name instanceof Node\Identifier) {
            return [];
        }

        // abstract base constraints are never used directly
        if ($node->isAbstract()) {
            return [];
        }

        $className = (string) $node->namespacedName;
        if (!$this->reflectionProvider->hasClass($className)) {
            return [];
        }

        if (!$this->reflectionProvider->getClass($className)->is(Constraint::class)) {
            return [];
        }

        foreach ($node->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                if (\Attribute::class === $attr->name->toString()) {
                    return [];
                }
            }
        }

        $ruleError = RuleErrorBuilder::message(sprintf(
            'Class "%s" extends Constraint but is missing the #[\Attribute] attribute. Add it, so the constraint can be used as an attribute on properties, as Symfony convention.',
            $className
        ))
            ->identifier('mautic.constraintAttribute')
            ->build();

        return [$ruleError];
    }
}
