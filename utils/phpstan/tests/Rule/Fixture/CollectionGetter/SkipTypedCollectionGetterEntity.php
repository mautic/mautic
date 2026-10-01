<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\CollectionGetter;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

class SkipTypedCollectionGetterEntity
{
    #[ORM\ManyToMany(targetEntity: \stdClass::class)]
    private $ipAddresses;

    #[ORM\ManyToOne(targetEntity: \stdClass::class)]
    private $owner;

    private $name;

    public function getIpAddresses(): Collection
    {
        return $this->ipAddresses;
    }

    public function getOwner()
    {
        return $this->owner;
    }

    public function getName()
    {
        return $this->name;
    }
}
