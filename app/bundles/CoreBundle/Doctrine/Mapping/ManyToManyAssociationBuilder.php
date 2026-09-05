<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Doctrine\Mapping;

/**
 * ManyToMany Association Builder.
 *
 * Override Doctrine's builder classes to add support to orphanRemoval until the fix is incorporated into Doctrine release
 * See @see https://github.com/doctrine/doctrine2/pull/1326/
 */
final class ManyToManyAssociationBuilder extends \Doctrine\ORM\Mapping\Builder\ManyToManyAssociationBuilder
{
    /**
     * Set orphanRemoval.
     *
     * @return ManyToManyAssociationBuilder
     */
    public function orphanRemoval(bool $orphanRemoval = true)
    {
        $this->mapping['orphanRemoval'] = $orphanRemoval;

        return $this;
    }
}
