<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\CollectionParam;

use Doctrine\Common\Collections\Collection;

class BareCollectionParam
{
    public function setItems(Collection $items): void
    {
    }

    /**
     * @param Collection $items
     */
    public function setDocblockedItems(Collection $items): void
    {
    }
}
