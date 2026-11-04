<?php

declare(strict_types=1);

namespace Utils\Rector;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Expression;

/**
 * Converts a loadMetadata() $builder->setTable('x') call into #[ORM\Table(name: 'x')].
 */
final class LoadMetadataTableToDoctrineAttributeRector extends AbstractLoadMetadataRector
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

        // Already mapped by an existing #[ORM\Table]: only drop a call that repeats it.
        if ($this->isHybrid && $this->hybridHasTable) {
            return $this->removeCallDuplicatedByAttribute($node, $loadMetadata, 'setTable', 'Table', 'name');
        }

        $owned = [];
        foreach ((array) $loadMetadata->stmts as $stmt) {
            if (!$stmt instanceof Expression || !$stmt->expr instanceof MethodCall) {
                continue;
            }

            foreach ($this->flattenChain($stmt->expr) ?? [] as $call) {
                $tableName = $this->argValue($call, 0);
                if ('setTable' !== $this->methodName($call) || null === $tableName) {
                    continue;
                }

                $this->insertClassAttribute($node, $this->attribute('Table', [$this->namedArg('name', $tableName)]));
                $owned[] = $call;
            }
        }

        if ([] === $owned) {
            return null;
        }

        $this->ensureEntityScaffolding($node, $loadMetadata);
        $this->removeOwnedCalls($loadMetadata, $owned);
        $this->removeLoadMetadataIfEmpty($node, $loadMetadata);

        return $node;
    }
}
