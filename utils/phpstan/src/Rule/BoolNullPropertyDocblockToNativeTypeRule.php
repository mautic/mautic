<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\Property;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

/**
 * A "@var bool|null" property docblock must be a native "?bool" type instead.
 *
 * @implements Rule<Property>
 *
 * @see \Utils\PHPStan\Tests\Rule\BoolNullPropertyDocblockToNativeTypeRuleTest
 */
final readonly class BoolNullPropertyDocblockToNativeTypeRule implements Rule
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
        if ($node->type instanceof Node && !$this->isMixed($node->type)) {
            return [];
        }

        $docComment = $node->getDocComment();
        if (null === $docComment) {
            return [];
        }

        $classReflection = $scope->getClassReflection();
        if (!$classReflection instanceof ClassReflection) {
            return [];
        }

        $varTags = $this->fileTypeMapper->getResolvedPhpDoc(
            $scope->getFile(),
            $classReflection->getName(),
            $scope->isInTrait() ? $scope->getTraitReflection()->getName() : null,
            null,
            $docComment->getText()
        )->getVarTags();

        if ([] === $varTags) {
            return [];
        }

        $ruleErrors = [];
        foreach ($node->props as $propertyProperty) {
            $name = $propertyProperty->name->toString();

            // a typed property redeclaring an untyped parent one is a fatal error
            if ($this->isDeclaredInParent($classReflection, $name)) {
                continue;
            }

            // a single "@var" tag is unnamed (key 0), a per-property one is keyed by its name
            $varTag = $varTags[$name] ?? $varTags[0] ?? null;
            if (null === $varTag || !$this->isBoolOrNull($varTag->getType())) {
                continue;
            }

            $ruleErrors[] = RuleErrorBuilder::message(sprintf(
                'Property "$%s" has a "bool|null" docblock, so it must declare the native "?bool" type instead.',
                $name
            ))
                ->identifier('mautic.boolNullPropertyDocblockToNativeType')
                ->line($propertyProperty->getStartLine())
                ->build();
        }

        return $ruleErrors;
    }

    private function isBoolOrNull(Type $type): bool
    {
        if (!TypeCombinator::containsNull($type)) {
            return false;
        }

        $withoutNull = TypeCombinator::removeNull($type);

        return !$withoutNull instanceof NeverType && $withoutNull->isBoolean()->yes();
    }

    private function isDeclaredInParent(ClassReflection $classReflection, string $propertyName): bool
    {
        $parentClassReflection = $classReflection->getParentClass();
        while ($parentClassReflection instanceof ClassReflection) {
            if ($parentClassReflection->hasNativeProperty($propertyName) && !$parentClassReflection->getNativeProperty($propertyName)->isPrivate()) {
                return true;
            }

            $parentClassReflection = $parentClassReflection->getParentClass();
        }

        return false;
    }

    private function isMixed(Node $type): bool
    {
        return $type instanceof Identifier && 'mixed' === strtolower($type->toString());
    }
}
