<?php

namespace Mautic\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

trait FiltersEntityTrait
{
    /**
     * @var array
     */
    #[Groups(['dynamicContent:read', 'dynamicContent:write'])]
    #[ORM\Column(type: 'array', nullable: true)]
    private $filters = [];

    public function getFilters(): array
    {
        return $this->filters ?: [];
    }

    /**
     * @return $this
     */
    public function setFilters(array $filters)
    {
        $this->isChanged('filters', $filters);
        $this->filters = $filters;

        return $this;
    }
}
