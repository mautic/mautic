<?php

declare(strict_types=1);

namespace Utils\Rector;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;

/**
 * Converts loadMetadata() $builder->createField(...) and ->addField(...) column mappings into
 * #[ORM\Column] (plus #[ORM\Id]/#[ORM\GeneratedValue] where applicable) on the matching property.
 */
final class LoadMetadataColumnToDoctrineAttributeRector extends AbstractLoadMetadataRector
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

        return $this->refactorFieldCreators($node, $loadMetadata, ['createField', 'addField']);
    }
}
