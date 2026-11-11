<?php

declare(strict_types=1);

namespace Utils\PHPStan\Collector;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\FileTypeMapper;
use Utils\PHPStan\Type\BoolNullTypeHelper;

/**
 * Collects every method declaration, so BoolNullReturnDocblockToNativeTypeRule can judge the whole override family.
 *
 * @implements Collector<ClassMethod, array{class: string, name: string, file: string, line: int, private: bool, native: string|null, candidate: bool}>
 */
final readonly class BoolNullReturnDocblockCollector implements Collector
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
     * @return array{class: string, name: string, file: string, line: int, private: bool, native: string|null, candidate: bool}|null
     */
    public function processNode(Node $node, Scope $scope): ?array
    {
        $classReflection = $scope->getClassReflection();
        if (!$classReflection instanceof ClassReflection) {
            return null;
        }

        $methodName = $node->name->toString();
        $native = BoolNullTypeHelper::printNativeType($node->returnType);

        $candidate = false;
        $docComment = $node->getDocComment();
        if (null === $native && null !== $docComment) {
            $returnTag = $this->fileTypeMapper->getResolvedPhpDoc(
                $scope->getFile(),
                $classReflection->getName(),
                $scope->isInTrait() ? $scope->getTraitReflection()->getName() : null,
                $methodName,
                $docComment->getText()
            )->getReturnTag();

            $candidate = null !== $returnTag && BoolNullTypeHelper::isBoolOrNull($returnTag->getType());
        }

        return [
            'class'     => $classReflection->getName(),
            'name'      => $methodName,
            'file'      => $scope->isInTrait() ? (string) $scope->getTraitReflection()->getFileName() : $scope->getFile(),
            'line'      => $node->getStartLine(),
            'private'   => $node->isPrivate(),
            'native'    => $native,
            'candidate' => $candidate,
        ];
    }
}
