<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\CollectionGetter;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

class UntypedCollectionGetterEntity
{
    #[ORM\ManyToMany(targetEntity: \stdClass::class)]
    private $ipAddresses;

    #[ORM\OneToMany(targetEntity: \stdClass::class, mappedBy: 'owner')]
    private $items;

    /**
     * @return Collection
     */
    public function getIpAddresses()
    {
        return $this->ipAddresses;
    }

    public function getItems(): iterable
    {
        return $this->items;
    }
}
