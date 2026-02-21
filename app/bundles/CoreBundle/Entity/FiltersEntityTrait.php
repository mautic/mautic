<?php

namespace Mautic\CoreBundle\Entity;

use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Symfony\Component\Serializer\Attribute\Groups;

trait FiltersEntityTrait
{
    /**
     * @var array
     */
    #[Groups(['dynamicContent:read', 'dynamicContent:write'])]
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
