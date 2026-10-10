<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\CollectionProperty;

use Doctrine\Common\Collections\Collection;

class BareCollectionProperty
{
    private Collection $items;

    /**
     * @var Collection
     */
    private Collection $docblockedItems;
}
