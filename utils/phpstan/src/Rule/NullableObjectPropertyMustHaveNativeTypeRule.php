<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Stmt\Property;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeWithClassName;
use PHPStan\Type\UnionType;

/**
 * A private property with no native type and no default value, whose only type is a "@var Object|null"
 * docblock, must declare the native nullable type instead, e.g. "private ?IpAddress $ipAddress = null".
 *
 * @implements Rule<Property>
 *
 * @see \Utils\PHPStan\Tests\Rule\NullableObjectPropertyMustHaveNativeTypeRuleTest
 */
final readonly class NullableObjectPropertyMustHaveNativeTypeRule implements Rule
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
        // only private, still untyped properties
        if (!$node->isPrivate() || $node->type instanceof Node) {
            return [];
        }

        $docComment = $node->getDocComment();
        if (null === $docComment) {
            return [];
        }

        $varTagTypes = $this->resolveVarTagTypes($node, $scope, $docComment->getText());

        $ruleErrors = [];

        foreach ($node->props as $propertyProperty) {
            // a default value means the signature is intentional, leave it alone
            if ($propertyProperty->default instanceof Node) {
                continue;
            }

            $propertyName = $propertyProperty->name->toString();
            $varTagType = $varTagTypes[$propertyName] ?? (1 === count($varTagTypes) ? reset($varTagTypes) : null);
            if (!$varTagType instanceof Type) {
                continue;
            }

            $className = $this->matchNullableObjectClassName($varTagType);
            if (null === $className) {
                continue;
            }

            $shortClassName = $this->resolveShortClassName($className);

            $ruleErrors[] = RuleErrorBuilder::message(sprintf(
                'Property "$%s" should declare native type "?%s = null" instead of a "@var" docblock only.',
                $propertyName,
                $shortClassName
            ))
                ->identifier('mautic.nullableObjectPropertyMustHaveNativeType')
                ->line($propertyProperty->getStartLine())
                ->build();
        }

        return $ruleErrors;
    }

    /**
     * @return array<string|int, Type>
     */
    private function resolveVarTagTypes(Property $property, Scope $scope, string $docText): array
    {
        $resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
            $scope->getFile(),
            $scope->isInClass() ? $scope->getClassReflection()->getName() : null,
            $scope->isInTrait() ? $scope->getTraitReflection()->getName() : null,
            null,
            $docText
        );

        $varTagTypes = [];
        foreach ($resolvedPhpDoc->getVarTags() as $key => $varTag) {
            $varTagTypes[$key] = $varTag->getType();
        }

        return $varTagTypes;
    }

    private function matchNullableObjectClassName(Type $type): ?string
    {
        if (!$type instanceof UnionType) {
            return null;
        }

        $innerTypes = $type->getTypes();
        if (2 !== count($innerTypes)) {
            return null;
        }

        $hasNull = false;
        $className = null;

        foreach ($innerTypes as $innerType) {
            if ($innerType instanceof NullType) {
                $hasNull = true;
                continue;
            }

            if ($innerType instanceof TypeWithClassName) {
                $className = $innerType->getClassName();
                continue;
            }

            // any other member (scalar, array, second object) disqualifies the match
            return null;
        }

        if (!$hasNull || null === $className) {
            return null;
        }

        return $className;
    }

    private function resolveShortClassName(string $className): string
    {
        $lastSeparatorPosition = strrpos($className, '\\');
        if (false === $lastSeparatorPosition) {
            return $className;
        }

        return substr($className, $lastSeparatorPosition + 1);
    }
}
