<?php

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
    public function getFilters()
    {
        return $this->filters ?: [];
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
