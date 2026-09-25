<?php

declare(strict_types=1);

namespace Utils\Rector;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Expression;

/**
 * Converts a loadMetadata() $builder->setCustomRepositoryClass(X::class) call into the
 * repositoryClass argument of the class-level #[ORM\Entity] attribute.
 */
final class LoadMetadataRepositoryToDoctrineAttributeRector extends AbstractLoadMetadataRector
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

        // Already declared on the existing #[ORM\Entity]: only drop a call that repeats it.
        if ($this->isHybrid && $this->hybridEntityHasRepositoryClass) {
            return $this->removeCallDuplicatedByAttribute($node, $loadMetadata, 'setCustomRepositoryClass', 'Entity', 'repositoryClass');
        }

        $owned          = [];
        $repositoryArgs = [];
        foreach ((array) $loadMetadata->stmts as $stmt) {
            if (!$stmt instanceof Expression || !$stmt->expr instanceof MethodCall) {
                continue;
            }

            foreach ($this->flattenChain($stmt->expr) ?? [] as $call) {
                $repositoryClass = $this->argValue($call, 0);
                if ('setCustomRepositoryClass' !== $this->methodName($call) || null === $repositoryClass) {
                    continue;
                }

                $repositoryArgs[] = $this->namedArg('repositoryClass', $repositoryClass);
                $owned[]          = $call;
            }
        }

        if ([] === $owned) {
            return null;
        }

        $this->ensureEntityScaffolding($node, $loadMetadata);

        $root = $this->findAttribute($node->attrGroups, ['Entity', 'MappedSuperclass']);
        if (null === $root) {
            return null;
        }

        foreach ($repositoryArgs as $repositoryArg) {
            $root->args[] = $repositoryArg;
        }

        $this->removeOwnedCalls($loadMetadata, $owned);
        $this->removeLoadMetadataIfEmpty($node, $loadMetadata);

        return $node;
    }
}
