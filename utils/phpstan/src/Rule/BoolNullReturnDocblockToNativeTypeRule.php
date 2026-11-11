<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\NeverType;
use PHPStan\Type\TypeCombinator;

/**
 * A "@return bool|null" docblock must be a native "?bool" return type instead.
 *
 * @implements Rule<ClassMethod>
 *
 * @see \Utils\PHPStan\Tests\Rule\BoolNullReturnDocblockToNativeTypeRuleTest
 */
final readonly class BoolNullReturnDocblockToNativeTypeRule implements Rule
{
    public function __construct(
        private FileTypeMapper $fileTypeMapper
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
        if ($node->returnType instanceof Node && !$this->isMixed($node->returnType)) {
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

        $methodName = $node->name->toString();

        // changing the native return type of an overridden method would break the parent signature
        if ($this->overridesAncestorMethod($classReflection, $methodName)) {
            return [];
        }

        $returnTag = $this->fileTypeMapper->getResolvedPhpDoc(
            $scope->getFile(),
            $classReflection->getName(),
            $scope->isInTrait() ? $scope->getTraitReflection()->getName() : null,
            $methodName,
            $docComment->getText()
        )->getReturnTag();

        if (null === $returnTag) {
            return [];
        }

        $type = $returnTag->getType();
        $withoutNull = TypeCombinator::removeNull($type);
        if (!TypeCombinator::containsNull($type) || $withoutNull instanceof NeverType || !$withoutNull->isBoolean()->yes()) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf(
                'Method "%s()" has a "bool|null" return docblock, so it must declare the native "?bool" return type instead.',
                $methodName
            ))
                ->identifier('mautic.boolNullReturnDocblockToNativeType')
                ->line($node->getStartLine())
                ->build(),
        ];
    }

    private function overridesAncestorMethod(ClassReflection $classReflection, string $methodName): bool
    {
        foreach ($classReflection->getAncestors() as $ancestor) {
            if ($ancestor->getName() === $classReflection->getName() || $ancestor->isTrait()) {
                continue;
            }

            if (!$ancestor->hasNativeMethod($methodName)) {
                continue;
            }

            if ($ancestor->getNativeMethod($methodName)->isPrivate()) {
                continue;
            }

            return true;
        }

        return false;
    }

    private function isMixed(Node $type): bool
    {
        return $type instanceof Identifier && 'mixed' === strtolower($type->toString());
    }
}
