<?php

declare(strict_types=1);

namespace Utils\PHPStan\Collector;

use PhpParser\Node;
use PhpParser\Node\Stmt\Property;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\FileTypeMapper;
use Utils\PHPStan\Type\BoolNullTypeHelper;

/**
 * Collects every property declaration, so BoolNullPropertyDocblockToNativeTypeRule can judge the whole redeclaration family.
 *
 * @implements Collector<Property, list<array{class: string, name: string, file: string, line: int, private: bool, native: string|null, candidate: bool}>>
 */
final readonly class BoolNullPropertyDocblockCollector implements Collector
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
     * @return list<array{class: string, name: string, file: string, line: int, private: bool, native: string|null, candidate: bool}>|null
     */
    public function processNode(Node $node, Scope $scope): ?array
    {
        $classReflection = $scope->getClassReflection();
        if (!$classReflection instanceof ClassReflection) {
            return null;
        }

        $native = BoolNullTypeHelper::printNativeType($node->type);

        $varTags = [];
        $docComment = $node->getDocComment();
        if (null === $native && null !== $docComment) {
            $varTags = $this->fileTypeMapper->getResolvedPhpDoc(
                $scope->getFile(),
                $classReflection->getName(),
                $scope->isInTrait() ? $scope->getTraitReflection()->getName() : null,
                null,
                $docComment->getText()
            )->getVarTags();
        }

        $entries = [];
        foreach ($node->props as $propertyProperty) {
            $name = $propertyProperty->name->toString();

            // a single "@var" tag is unnamed (key 0), a per-property one is keyed by its name
            $varTag = $varTags[$name] ?? $varTags[0] ?? null;

            $entries[] = [
                'class'     => $classReflection->getName(),
                'name'      => $name,
                'file'      => $scope->isInTrait() ? (string) $scope->getTraitReflection()->getFileName() : $scope->getFile(),
                'line'      => $propertyProperty->getStartLine(),
                'private'   => $node->isPrivate(),
                'native'    => $native,
                'candidate' => null !== $varTag && BoolNullTypeHelper::isBoolOrNull($varTag->getType()),
            ];
        }

        return $entries;
    }
}
