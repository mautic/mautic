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

    /**
     * @var Collection<int, \stdClass>
     */
    private ?Collection $nullableItems;

    /**
     * @var Collection<int, \stdClass>|null
     */
    private ?Collection $nullableUnionItems;

    private string $name;
}
