<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Entity;

use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

/**
 * Trait FiltersEntityTrait.
 */
trait FiltersEntityTrait
{
    /**
     * @var array
     */
    private $filters = [];

    /**
     * @param ClassMetadataBuilder $builder
     */
    protected static function addFiltersMetadata(ClassMetadataBuilder $builder)
    {
        $builder->createField('filters', 'array')
                ->columnName('filters')
                ->nullable()
                ->build();
    }

    /**
     * @return array
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * @param array $filters
     *
     * @return $this
     */
    public function setFilters($filters)
    {
        $this->isChanged('filters', $filters);
        $this->filters = $filters;

        return $this;
    }
}
