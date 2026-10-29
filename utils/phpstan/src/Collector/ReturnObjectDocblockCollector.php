<?php

declare(strict_types=1);

namespace Utils\PHPStan\Collector;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Node\MethodReturnStatementsNode;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StaticType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

/**
 * Collects, for every method, an object-type return docblock that is not backed by a native return type and
 * whose body really does return that object. The override family is judged later by
 * ReturnDocblockToNativeTypeRule, so a return type is only narrowed when no method in the family stands in the
 * way.
 *
 * A plain single class, "self", "static" and "$this" are promoted; nullable, unions, generics, arrays and
 * scalars are left alone.
 *
 * @phpstan-type ReturnCandidate array{type: string}
 *
 * @implements Collector<MethodReturnStatementsNode, array{class: string, method: string, line: int, candidate: ReturnCandidate|null}>
 */
final readonly class ReturnObjectDocblockCollector implements Collector
{
    /**
     * A getter returning one of these is a service getter (see NoServiceGetterRule); typing it would turn that
     * rule on, so leave it alone.
     *
     * @var string[]
     */
    private const array SERVICE_SUFFIXES = ['Repository', 'Model', 'Factory', 'Provider', 'Service', 'Handler'];

    public function getNodeType(): string
    {
        return MethodReturnStatementsNode::class;
    }

    /**
     * @param MethodReturnStatementsNode $node
     *
     * @return array{class: string, method: string, line: int, candidate: array{type: string}|null}
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $classReflection = $node->getClassReflection();
        $methodName = $node->getMethodName();

        // every declaration is emitted so the rule can see the whole override family, not just the candidates
        $entry = [
            'class'     => $classReflection->getName(),
            'method'    => $methodName,
            'line'      => $node->getStartLine(),
            'candidate' => null,
        ];

        if ($node->hasNativeReturnTypehint() || $node->isGenerator()) {
            return $entry;
        }

        if ('__construct' === strtolower($methodName)) {
            return $entry;
        }

        // the declared return type, which with no native type is the "@return" docblock type
        $docType = $node->getMethodReflection()->getVariants()[0]->getReturnType();

        $nativeType = $docType;

        // "static" and "$this" map to a native "static" return; in a final class "static" resolves to a plain
        // object type, so the raw tag is read to tell it apart from "self"
        $rawReturnTag = $this->rawReturnTag($node);
        if ($docType instanceof StaticType || 'static' === $rawReturnTag || '$this' === $rawReturnTag) {
            $typeString = 'static';
        } else {
            if (!$docType->isObject()->yes()) {
                return $entry;
            }

            $objectClassNames = $docType->getObjectClassNames();
            if (1 !== count($objectClassNames)) {
                return $entry;
            }

            // only a plain class type: nullable, generics and intersections would be lost on promotion
            if (!$docType->equals(new ObjectType($objectClassNames[0]))) {
                return $entry;
            }

            // a Doctrine collection getter returns ArrayCollection before persist and PersistentCollection
            // after, so a native ArrayCollection return type would fatal at runtime
            if (str_starts_with($objectClassNames[0], 'Doctrine\\Common\\Collections\\')) {
                return $entry;
            }

            // date getters back a nullable column but are routinely documented as a non-null "@return
            // DateTimeInterface"; a native non-null type would fatal when the column is null
            if ($this->isDateTimeType($objectClassNames[0])) {
                return $entry;
            }

            foreach (self::SERVICE_SUFFIXES as $suffix) {
                if (str_ends_with($objectClassNames[0], $suffix)) {
                    return $entry;
                }
            }

            // "@return self" resolves to the declaring class, so render it back as "self"
            $typeString = $objectClassNames[0] === $classReflection->getName() ? 'self' : $objectClassNames[0];
        }

        // the docblock must tell the truth: everything the body actually returns has to fit the native type
        if (!$nativeType->isSuperTypeOf($this->inferReturnType($node))->yes()) {
            return $entry;
        }

        $entry['candidate'] = [
            'type' => $typeString,
        ];

        return $entry;
    }

    private function rawReturnTag(MethodReturnStatementsNode $node): ?string
    {
        $docComment = $node->getClassReflection()
            ->getNativeReflection()
            ->getMethod($node->getMethodName())
            ->getDocComment();

        if (false === $docComment) {
            return null;
        }

        if (1 === preg_match('/@return\s+(\S+)/', $docComment, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function isDateTimeType(string $className): bool
    {
        return in_array(
            ltrim($className, '\\'),
            ['DateTime', 'DateTimeImmutable', 'DateTimeInterface'],
            true
        );
    }

    private function inferReturnType(MethodReturnStatementsNode $node): Type
    {
        $types = [];
        foreach ($node->getReturnStatements() as $returnStatement) {
            $returnNode = $returnStatement->getReturnNode();
            $types[] = null === $returnNode->expr
                ? new NullType()
                : $returnStatement->getScope()->getType($returnNode->expr);
        }

        return [] === $types ? new NeverType() : TypeCombinator::union(...$types);
    }
}
