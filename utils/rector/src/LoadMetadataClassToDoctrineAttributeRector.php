<?php

declare(strict_types=1);

namespace Utils\Rector;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Expression;

/**
 * Converts loadMetadata() $builder->setMappedSuperClass() into #[ORM\MappedSuperclass] and adds the
 * mandatory class markers (#[ORM\Entity] otherwise, plus #[ORM\ChangeTrackingPolicy]) via the
 * shared scaffolding once any conversion has happened.
 */
final class LoadMetadataClassToDoctrineAttributeRector extends AbstractLoadMetadataRector
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

        $owned = [];
        foreach ((array) $loadMetadata->stmts as $stmt) {
            if (!$stmt instanceof Expression || !$stmt->expr instanceof MethodCall) {
                continue;
            }

            foreach ($this->flattenChain($stmt->expr) ?? [] as $call) {
                if ('setMappedSuperClass' === $this->methodName($call) && [] === $call->args) {
                    $owned[] = $call;
                }
            }
        }

        if ([] === $owned) {
            return null;
        }

        // Run scaffolding before trimming so it still sees setMappedSuperClass and picks the
        // #[ORM\MappedSuperclass] root over #[ORM\Entity].
        $this->ensureEntityScaffolding($node, $loadMetadata);
        $this->removeOwnedCalls($loadMetadata, $owned);
        $this->removeLoadMetadataIfEmpty($node, $loadMetadata);

        return $node;
    }
}
