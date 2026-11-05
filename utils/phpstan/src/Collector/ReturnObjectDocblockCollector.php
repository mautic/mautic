<?php

declare(strict_types=1);

namespace Utils\PHPStan\Collector;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Node\MethodReturnStatementsNode;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StaticType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;

/**
 * Collects, for every method, an object-type return docblock that is not backed by a native return type and
 * whose body really does return that object. The override family is judged later by
 * ReturnDocblockToNativeTypeRule, so a return type is only narrowed when no method in the family stands in the
 * way.
 *
 * A plain single class, "self", "static"/"$this", a nullable object, a union of objects, an intersection of
 * objects and the bare "iterable"/"Traversable" keyword (including a generator so documented) are promoted;
 * generics, a "Generator" tag, arrays and scalars are left alone.
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

        if ($node->hasNativeReturnTypehint()) {
            return $entry;
        }

        if ('__construct' === strtolower($methodName)) {
            return $entry;
        }

        // the declared return type, which with no native type is the "@return" docblock type
        $docType = $node->getMethodReflection()->getVariants()[0]->getReturnType();

        $rawReturnTag = $this->rawReturnTag($node);

        // a generator yields a Generator at runtime, so "iterable" is a safe native supertype, but only when
        // the docblock is the bare "iterable"/"Traversable" keyword: a "Generator" or generic tag carries a
        // contract (send/return, key/value types) that a native "iterable" would silently drop
        if ($node->isGenerator()) {
            if ($docType->isIterable()->yes() && $this->isPlainIterableKeyword($rawReturnTag)) {
                $entry['candidate'] = ['type' => 'iterable'];
            }

            return $entry;
        }

        $nativeType = $docType;

        // "static" and "$this" map to a native "static" return; in a final class "static" resolves to a plain
        // object type, so the raw tag is read to tell it apart from "self"
        if ($docType instanceof StaticType || 'static' === $rawReturnTag || '$this' === $rawReturnTag) {
            $typeString = 'static';
        } else {
            $typeString = $this->buildNativeType($docType, $classReflection->getName(), $rawReturnTag);
            if (null === $typeString) {
                return $entry;
            }
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

    /**
     * Renders the native return type for a non-static, non-generator docblock, or null when it is not
     * promotable. A single object, "self", a nullable object, a union of objects, an intersection of objects
     * and the "iterable" keyword are promoted; a nullable union renders as "A|B|null" because PHP forbids the
     * "?" shorthand alongside a union.
     */
    private function buildNativeType(Type $docType, string $declaringClass, ?string $rawReturnTag): ?string
    {
        // the "iterable" keyword: iterable but neither a definite array nor a definite object (a concrete
        // Traversable object is handled as a plain object below)
        if ($docType->isIterable()->yes() && !$docType->isArray()->yes() && !$docType->isObject()->yes()) {
            // a generic tag (iterable<int, string>) carries key/value types a bare "iterable" would drop
            if (null !== $rawReturnTag && str_contains($rawReturnTag, '<')) {
                return null;
            }

            return 'iterable';
        }

        // an intersection of plain objects, rendered "A&B"; an "instanceof IntersectionType" check is
        // deprecated by the PHPStan API, so it is told apart from a union by holding several object class
        // names yet equalling a pure intersection of them. Anything that is not an intersection falls through
        // to the single-object/union handling below.
        if ($docType->isObject()->yes()) {
            $objectClassNames = $docType->getObjectClassNames();
            if (count($objectClassNames) >= 2) {
                $intersectionType = $this->buildIntersectionType($docType, $objectClassNames, $declaringClass);
                if (null !== $intersectionType) {
                    return $intersectionType;
                }
            }
        }

        $members = $docType instanceof UnionType ? $docType->getTypes() : [$docType];

        $nullable = false;
        $names = [];
        foreach ($members as $member) {
            if ($member->isNull()->yes()) {
                $nullable = true;

                continue;
            }

            $name = $this->objectMemberName($member, $declaringClass);
            if (null === $name) {
                return null;
            }

            $names[] = $name;
        }

        if ([] === $names) {
            return null;
        }

        if ($nullable && 1 === count($names)) {
            return '?'.$names[0];
        }

        if ($nullable) {
            $names[] = 'null';
        }

        return implode('|', $names);
    }

    /**
     * Renders an intersection of plain objects as "A&B", or null when the type is not a pure intersection of
     * promotable plain classes.
     *
     * @param string[] $objectClassNames
     */
    private function buildIntersectionType(Type $docType, array $objectClassNames, string $declaringClass): ?string
    {
        $plainMembers = array_map(static fn (string $className): ObjectType => new ObjectType($className), $objectClassNames);
        if (!$docType->equals(new IntersectionType($plainMembers))) {
            return null;
        }

        $names = [];
        foreach ($objectClassNames as $className) {
            if (!$this->isPromotableClassName($className)) {
                return null;
            }

            // PHP forbids "self" inside an intersection type, so the declaring class keeps its full name here
            $names[] = $className;
        }

        return implode('&', $names);
    }

    /**
     * The plain class name of a single promotable object member ("self" for the declaring class), or null for
     * anything that would lose meaning as a native type: a generic, an intersection, a Doctrine collection, a
     * date getter or a service getter.
     */
    private function objectMemberName(Type $member, string $declaringClass): ?string
    {
        if (!$member->isObject()->yes()) {
            return null;
        }

        $objectClassNames = $member->getObjectClassNames();
        if (1 !== count($objectClassNames)) {
            return null;
        }

        $className = $objectClassNames[0];

        // only a plain class type: generics and intersections would be lost on promotion
        if (!$member->equals(new ObjectType($className))) {
            return null;
        }

        if (!$this->isPromotableClassName($className)) {
            return null;
        }

        // "@return self" resolves to the declaring class, so render it back as "self"
        return $className === $declaringClass ? 'self' : $className;
    }

    /**
     * Rejects class names that would lose meaning or fatal as a native type: a Doctrine collection, a date
     * getter or a service getter.
     */
    private function isPromotableClassName(string $className): bool
    {
        // a Doctrine collection getter returns ArrayCollection before persist and PersistentCollection
        // after, so a native ArrayCollection return type would fatal at runtime
        if (str_starts_with($className, 'Doctrine\\Common\\Collections\\')) {
            return false;
        }

        // date getters back a nullable column but are routinely documented as a non-null "@return
        // DateTimeInterface"; a native non-null type would fatal when the column is null
        if ($this->isDateTimeType($className)) {
            return false;
        }

        foreach (self::SERVICE_SUFFIXES as $suffix) {
            if (str_ends_with($className, $suffix)) {
                return false;
            }
        }

        return true;
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

        // a body that can fall off the end returns null implicitly; fold that in so a non-null native type
        // (": iterable", ": Foo") is never suggested for a method that can return null at runtime
        if (!$node->getStatementResult()->isAlwaysTerminating()) {
            $types[] = new NullType();
        }

        return [] === $types ? new NeverType() : TypeCombinator::union(...$types);
    }

    // the bare "iterable"/"Traversable" keyword, with no generics; anything more specific (a "Generator"
    // class or a generic tag) would lose meaning as a native "iterable"
    private function isPlainIterableKeyword(?string $rawReturnTag): bool
    {
        if (null === $rawReturnTag) {
            return false;
        }

        $normalized = strtolower(ltrim($rawReturnTag, '\\'));

        return 'iterable' === $normalized || 'traversable' === $normalized;
    }
}
