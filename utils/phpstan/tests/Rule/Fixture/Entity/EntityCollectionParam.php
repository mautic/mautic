<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

final class EntityCollectionParam
{
    public function setItems(ArrayCollection $items): void
    {
    }

    public function setInterfaceItems(Collection $items): void
    {
    }

    public function setScalar(string $name): void
    {
    }
}
