<?php

declare(strict_types=1);

namespace Utils\Rector;

use PhpParser\Node;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Expression;

/**
 * Converts loadMetadata() $builder->addIndex(...) and ->addUniqueConstraint(...) calls into
 * class-level #[ORM\Index] and #[ORM\UniqueConstraint] attributes.
 */
final class LoadMetadataIndexToDoctrineAttributeRector extends AbstractLoadMetadataRector
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

        $owned      = [];
        $attributes = [];
        foreach ((array) $loadMetadata->stmts as $stmt) {
            if (!$stmt instanceof Expression || !$stmt->expr instanceof MethodCall) {
                continue;
            }

            foreach ($this->flattenChain($stmt->expr) ?? [] as $call) {
                $attribute = match ($this->methodName($call)) {
                    'addIndex'            => $this->indexAttribute($call),
                    'addUniqueConstraint' => $this->uniqueConstraintAttribute($call),
                    default               => null,
                };

                if (null === $attribute) {
                    continue;
                }

                $attributes[] = $attribute;
                $owned[]      = $call;
            }
        }

        if ([] === $owned) {
            return null;
        }

        $this->ensureEntityScaffolding($node, $loadMetadata);
        foreach ($attributes as $attribute) {
            $this->insertClassAttribute($node, $attribute);
        }

        $this->removeOwnedCalls($loadMetadata, $owned);
        $this->removeLoadMetadataIfEmpty($node, $loadMetadata);

        return $node;
    }

    private function indexAttribute(MethodCall $call): ?AttributeGroup
    {
        $columns = $this->argValue($call, 0);
        $name    = $this->argValue($call, 1);
        if (!$columns instanceof Array_ || null === $name) {
            return null;
        }

        $indexArgs = [
            $this->namedArg('columns', $columns),
            $this->namedArg('name', $name),
        ];

        // Optional flags array (addIndex(cols, name, ['fulltext'])).
        $flags = $this->argValue($call, 2);
        if ($flags instanceof Array_) {
            $indexArgs[] = $this->namedArg('flags', $flags);
        }

        // Optional options array (addIndex(cols, name, null, ['lengths' => ...])).
        $options = $this->argValue($call, 3);
        if ($options instanceof Array_) {
            $indexArgs[] = $this->namedArg('options', $options);
        }

        return $this->attribute('Index', $indexArgs);
    }

    private function uniqueConstraintAttribute(MethodCall $call): ?AttributeGroup
    {
        $columns = $this->argValue($call, 0);
        $name    = $this->argValue($call, 1);
        if (2 !== count($call->args) || !$columns instanceof Array_ || null === $name) {
            return null;
        }

        return $this->attribute('UniqueConstraint', [
            $this->namedArg('columns', $columns),
            $this->namedArg('name', $name),
        ]);
    }
}
