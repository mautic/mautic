<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\Property;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

/**
 * A property with a default value, documented with a non-nullable array ("Foo[]", "array") or
 * "iterable" "@var" tag, must declare the matching native "array"/"iterable" type. A missing or "mixed"
 * native type is reported. A property with no default is skipped, as adding a native type there would
 * turn an implicit null into an uninitialized typed property. A nullable ("|null") docblock is skipped
 * too: making the property nullable tends to ripple into getters and offset access that assume an array.
 * A Doctrine-mapped property is skipped as well, since its real type comes from the database schema.
 *
 * @implements Rule<Property>
 *
 * @see \Utils\PHPStan\Tests\Rule\NativeTypeForArrayOrIterablePropertyRuleTest
 */
final readonly class NativeTypeForArrayOrIterablePropertyRule implements Rule
{
    public function __construct(
        private FileTypeMapper $fileTypeMapper
    ) {
    }

    public function getNodeType(): string
    {
        return Property::class;
    }

    /**
     * @param Property $node
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$this->isMissingOrMixed($node->type)) {
            return [];
        }

        // a Doctrine-mapped property is governed by the database schema, not the docblock: the column may
        // be nullable or the value coerced on hydration, so a native "array" type is unsafe here
        if ($this->isDoctrineMapped($node)) {
            return [];
        }

        $docComment = $node->getDocComment();
        if (null === $docComment) {
            return [];
        }

        $classReflection = $scope->getClassReflection();
        if (null === $classReflection) {
            return [];
        }

        $resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
            $scope->getFile(),
            $classReflection->getName(),
            $scope->isInTrait() ? $scope->getTraitReflection()->getName() : null,
            null,
            $docComment->getText()
        );

        $varTags = $resolvedPhpDoc->getVarTags();
        if ([] === $varTags) {
            return [];
        }

        // a single, shared "@var" tag is unnamed (key 0); a per-property tag is keyed by its name
        $ruleErrors = [];
        foreach ($node->props as $propertyProperty) {
            // no default value means a native type would make the property uninitialized, changing its
            // runtime behaviour; leave it to an explicit default being added first
            if (!$propertyProperty->default instanceof Node) {
                continue;
            }

            $name = $propertyProperty->name->toString();
            $varTag = $varTags[$name] ?? $varTags[0] ?? null;
            if (null === $varTag) {
                continue;
            }

            $requiredType = $this->resolveRequiredNativeType($varTag->getType());
            if (null === $requiredType) {
                continue;
            }

            $ruleErrors[] = RuleErrorBuilder::message(sprintf(
                'Property "$%s" has a "%s" docblock, so it must declare the native "%s" type.',
                $name,
                $requiredType,
                $requiredType
            ))
                ->identifier('mautic.nativeTypeForArrayOrIterableDocblock')
                ->line($propertyProperty->getStartLine())
                ->build();
        }

        return $ruleErrors;
    }

    private function isDoctrineMapped(Property $property): bool
    {
        foreach ($property->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                if (str_contains(strtolower($attr->name->toString()), 'orm\\')) {
                    return true;
                }
            }
        }

        return false;
    }

    private function isMissingOrMixed(?Node $type): bool
    {
        if (!$type instanceof Node) {
            return true;
        }

        return $type instanceof Identifier && 'mixed' === strtolower($type->toString());
    }

    private function resolveRequiredNativeType(Type $type): ?string
    {
        // a nullable docblock is left alone; making the property "?array" tends to break getters and
        // offset access elsewhere that treat it as a plain array
        if (TypeCombinator::containsNull($type)) {
            return null;
        }

        if ($type->isArray()->yes()) {
            return 'array';
        }

        if ($type->isIterable()->yes() && !$type->isObject()->yes()) {
            return 'iterable';
        }

        return null;
    }
}
