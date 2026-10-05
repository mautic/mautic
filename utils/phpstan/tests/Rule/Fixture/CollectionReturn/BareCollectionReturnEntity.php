<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\CollectionReturn;

use Doctrine\Common\Collections\Collection;

class BareCollectionReturnEntity
{
    private Collection $ipAddresses;

    public function getIpAddresses(): Collection
    {
        return $this->ipAddresses;
    }

    /**
     * @return Collection
     */
    public function getItems(): Collection
    {
        return $this->items;
    }
}
