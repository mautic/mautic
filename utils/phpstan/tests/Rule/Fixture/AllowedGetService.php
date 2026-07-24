<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

use Doctrine\Common\Collections\ArrayCollection;

// unrelated ->get() calls must be skipped
class AllowedGetService
{
    /**
     * @param ArrayCollection<int, object> $items
     */
    public function run(ArrayCollection $items): void
    {
        $items->get(0);
    }
}
