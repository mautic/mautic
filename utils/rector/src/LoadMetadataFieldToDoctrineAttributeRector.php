<?php

declare(strict_types=1);

namespace Utils\Rector;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Property;

/**
 * Converts loadMetadata() field and association builder calls (createField, addField,
 * createManyToOne/OneToMany/OneToOne, createManyToMany) into #[ORM\Column]/#[ORM\ManyToOne]/...
 * attributes on the matching property. A property that lives in a parent is redeclared here.
 */
final class LoadMetadataFieldToDoctrineAttributeRector extends AbstractLoadMetadataRector
{
    public function refactor(Node $node): ?Node
    {
        if (!$node instanceof Class_) {
            return null;
        }

        $loadMetadata = $this->getLoadMetadata($node);
        if (null === $loadMetadata) {
            return null;
        }

        $this->initHybridState($node);

        $owned            = [];
        $propertyResolved = [];
        $newProperties    = [];

        foreach ((array) $loadMetadata->stmts as $stmt) {
            if (!$stmt instanceof Expression || !$stmt->expr instanceof MethodCall) {
                continue;
            }

            $calls    = $this->flattenChain($stmt->expr);
            $segments = null === $calls ? null : $this->splitIntoSegments($calls);
            if (null === $segments) {
                continue;
            }

            foreach ($segments as $segment) {
                if (!in_array($this->methodName($segment[0]), self::FIELD_CREATOR_METHODS, true)) {
                    continue;
                }

                $fields = $this->handleFieldChain($segment);
                if (null === $fields) {
                    continue;
                }

                $resolved = $this->resolveFields($node, $fields);
                if (null === $resolved) {
                    continue;
                }

                [$resolvedProperties, $resolvedNewProperties] = $resolved;

                $propertyResolved = array_merge($propertyResolved, $resolvedProperties);
                $newProperties    = array_merge($newProperties, $resolvedNewProperties);
                $owned            = array_merge($owned, $segment);
            }
        }

        if ([] === $owned && [] === $newProperties) {
            return null;
        }

        foreach ($propertyResolved as [$property, $attributeGroups]) {
            $property->attrGroups = array_merge($property->attrGroups, $attributeGroups);
        }

        $this->removeOwnedCalls($loadMetadata, $owned);
        $this->ensureEntityScaffolding($node, $loadMetadata);
        $this->removeLoadMetadataIfEmpty($node, $loadMetadata);
        $this->insertNewProperties($node, $newProperties);

        return $node;
    }

    /**
     * Resolves a segment's fields against the class body. Returns null - leaving the segment in
     * loadMetadata - when the class is hybrid and the property is already declared here, or when a
     * property is missing and there is no parent to redeclare it from.
     *
     * @param array<string, list<Node\AttributeGroup>> $fields
     *
     * @return array{0: list<array{0: Property|Param, 1: list<Node\AttributeGroup>}>, 1: list<Property>}|null
     */
    private function resolveFields(Class_ $node, array $fields): ?array
    {
        $propertyResolved = [];
        $newProperties    = [];

        foreach ($fields as $fieldName => $attributeGroups) {
            $property = $this->findProperty($node, $fieldName);

            // A hybrid class already maps its own fields via attributes, so a field whose property
            // is declared here is left behind to avoid duplicating it.
            if ($this->isHybrid && null !== $property) {
                return null;
            }

            if ($property instanceof Property || $property instanceof Param) {
                $propertyResolved[] = [$property, $attributeGroups];

                continue;
            }

            // The property is not declared in this class. When it extends a parent, the mapped
            // property lives there - often a vendor base class we cannot annotate - so redeclare it.
            if (null === $node->extends) {
                return null;
            }

            $newProperties[] = $this->redeclaredProperty($node, $fieldName, $attributeGroups);
        }

        return [$propertyResolved, $newProperties];
    }
}
