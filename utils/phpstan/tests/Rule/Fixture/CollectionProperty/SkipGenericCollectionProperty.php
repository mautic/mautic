<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\CollectionProperty;

use Doctrine\Common\Collections\Collection;

class SkipGenericCollectionProperty
{
    /**
     * @var Collection<int, \stdClass>
     */
    private Collection $items;

    private string $name;
}
