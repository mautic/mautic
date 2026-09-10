<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * An attribute can only be repeated on the same class, method or property when it is
 * declared with the \Attribute::IS_REPEATABLE flag. Report every non-repeatable one used twice.
 *
 * @implements Rule<Node\Stmt>
 */
final readonly class NoDuplicateNonRepeatableAttributeRule implements Rule
{
    public function __construct(
        private ReflectionProvider $reflectionProvider,
    ) {
    }

    public function getNodeType(): string
    {
        return Node\Stmt::class;
    }

    /**
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $elementType = $this->resolveElementType($node);
        if (null === $elementType) {
            return [];
        }

        /** @var Class_|ClassMethod|Property $node */
        $countByAttribute = [];
        foreach ($node->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                $attributeName = $attr->name->toString();
                $countByAttribute[$attributeName] = ($countByAttribute[$attributeName] ?? 0) + 1;
            }
        }

        $ruleErrors = [];
        foreach ($countByAttribute as $attributeName => $count) {
            if ($count < 2) {
                continue;
            }

            if ($this->isRepeatable($attributeName)) {
                continue;
            }

            $ruleErrors[] = RuleErrorBuilder::message(sprintf(
                'Attribute "#[%s]" is used %d times on the same %s, but is not repeatable. Add the \Attribute::IS_REPEATABLE flag to its #[\Attribute] declaration, or remove the duplicate.',
                $attributeName,
                $count,
                $elementType
            ))
                ->identifier('mautic.noDuplicateNonRepeatableAttribute')
                ->build();
        }

        return $ruleErrors;
    }

    private function resolveElementType(Node $node): ?string
    {
        if ($node instanceof Class_) {
            return 'class';
        }

        if ($node instanceof ClassMethod) {
            return 'method';
        }

        if ($node instanceof Property) {
            return 'property';
        }

        return null;
    }

    private function isRepeatable(string $attributeName): bool
    {
        if (!$this->reflectionProvider->hasClass($attributeName)) {
            // cannot confirm it is non-repeatable, so stay silent to avoid a false positive
            return true;
        }

        $nativeReflection = $this->reflectionProvider->getClass($attributeName)->getNativeReflection();
        foreach ($nativeReflection->getAttributes(\Attribute::class) as $reflectionAttribute) {
            $flags = $reflectionAttribute->getArguments()[0] ?? 0;

            return (bool) ($flags & \Attribute::IS_REPEATABLE);
        }

        // the class has no #[\Attribute] declaration, treat as non-repeatable
        return false;
    }
}
