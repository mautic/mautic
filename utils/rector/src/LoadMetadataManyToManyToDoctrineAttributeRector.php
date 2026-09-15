<?php

declare(strict_types=1);

namespace Utils\Rector;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;

/**
 * Converts a loadMetadata() $builder->createManyToMany(...) mapping into an #[ORM\ManyToMany]
 * attribute plus its #[ORM\JoinTable], #[ORM\JoinColumn] and #[ORM\InverseJoinColumn].
 */
final class LoadMetadataManyToManyToDoctrineAttributeRector extends AbstractLoadMetadataRector
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

        return $this->refactorFieldCreators($node, $loadMetadata, ['createManyToMany']);
    }
}
