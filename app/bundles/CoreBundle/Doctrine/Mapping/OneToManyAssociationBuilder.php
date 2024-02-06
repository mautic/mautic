<?php

namespace Mautic\CoreBundle\Doctrine\Mapping;

/**
 * OneToMany Association Builder.
 *
 * Override Doctrine's builder classes to add support to orphanRemoval until the fix is incorporated into Doctrine release
 * See @see https://github.com/doctrine/doctrine2/pull/1326/
 */
class OneToManyAssociationBuilder extends \Doctrine\ORM\Mapping\Builder\OneToManyAssociationBuilder
{
    /**
     * Set orphanRemoval.
     *
     * @param bool $orphanRemoval
     *
     * @return OneToManyAssociationBuilder
     */
    public function orphanRemoval($orphanRemoval = true)
    {
        $this->mapping['orphanRemoval'] = $orphanRemoval;

        return $this;
    }
}
