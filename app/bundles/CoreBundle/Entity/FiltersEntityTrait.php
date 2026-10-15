<?php

namespace Mautic\CoreBundle\Entity;

use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Symfony\Component\Serializer\Attribute\Groups;

trait FiltersEntityTrait
{
    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['dynamicContent:read', 'dynamicContent:write', 'focus:read', 'focus:write'])]
    private $filters = [];

    protected static function addFiltersMetadata(ClassMetadataBuilder $builder): void
    {
        $builder->createField('filters', 'array')
            ->columnName('filters')
            ->nullable()
            ->build();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getFilters()
    {
        return $this->filters ?: [];
    }

    /**
     * @param array<int, array<string, mixed>> $filters
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
