<?php

namespace Mautic\CoreBundle\Doctrine\Mapping;

/**
 * Override Doctrine's builder classes to add support to orphanRemoval until the fix is incorporated into Doctrine release
 * See @see https://github.com/doctrine/doctrine2/pull/1326/.
 *
 * Also gives support for allowing a many-to-one to be the primary key
 */
class AssociationBuilder extends \Doctrine\ORM\Mapping\Builder\AssociationBuilder
{
    /**
     * Set orphanRemoval.
     *
     * @param bool $orphanRemoval
     *
     * @return AssociationBuilder
     */
    public function orphanRemoval($orphanRemoval = true)
    {
        $this->mapping['orphanRemoval'] = $orphanRemoval;

        return $this;
    }

    /**
     * Allow a many-to-one to be the ID.
     *
     * @return $this
     */
    public function isPrimaryKey()
    {
        $this->mapping['id'] = true;

        return $this;
    }
}
