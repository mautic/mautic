<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\CollectionReturn;

use Doctrine\Common\Collections\Collection;

class SkipGenericCollectionReturnEntity
{
    private Collection $ipAddresses;

    private array $names;

    /**
     * @return Collection<int, \stdClass>
     */
    public function getIpAddresses(): Collection
    {
        return $this->ipAddresses;
    }

    /**
     * @return array<int, string>
     */
    public function getNames(): array
    {
        return $this->names;
    }
}
