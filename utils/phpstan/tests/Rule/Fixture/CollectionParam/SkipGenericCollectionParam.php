<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\CollectionParam;

use Doctrine\Common\Collections\Collection;

class SkipGenericCollectionParam
{
    /**
     * @param Collection<int, \stdClass> $items
     */
    public function setItems(Collection $items): void
    {
    }

    public function setName(string $name): void
    {
    }
}
