<?php

declare(strict_types=1);

namespace Utils\Rector;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;

/**
 * Converts loadMetadata() single-target association builders (createManyToOne, createOneToMany,
 * createOneToOne) into #[ORM\ManyToOne]/#[ORM\OneToMany]/#[ORM\OneToOne] attributes (plus their
 * #[ORM\JoinColumn]/#[ORM\OrderBy]) on the matching property.
 */
final class LoadMetadataAssociationToDoctrineAttributeRector extends AbstractLoadMetadataRector
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

        return $this->refactorFieldCreators($node, $loadMetadata, ['createManyToOne', 'createOneToMany', 'createOneToOne']);
    }
}
