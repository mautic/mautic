<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Doctrine\Mapping;

/**
 * ManyToMany Association Builder.
 *
 * Override Doctrine's builder classes to add support to orphanRemoval until the fix is incorporated into Doctrine release
 * See @link https://github.com/doctrine/doctrine2/pull/1326/
 */
class ManyToManyAssociationBuilder extends \Doctrine\ORM\Mapping\Builder\ManyToManyAssociationBuilder
{
    /**
     * Set orphanRemoval.
     *
     * @param bool $orphanRemoval
     *
     * @return ManyToManyAssociationBuilder
     */
    public function orphanRemoval($orphanRemoval = true)
    {
        $this->mapping['orphanRemoval'] = $orphanRemoval;

        return $this;
    }

    /**
     * @return ClassMetadataBuilder
     */
    public function build()
    {
        return parent::build();
    }
}
