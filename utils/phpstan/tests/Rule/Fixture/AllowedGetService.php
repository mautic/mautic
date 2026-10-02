<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

use Doctrine\Common\Collections\ArrayCollection;

// non-container ->get() calls must be skipped
class AllowedGetService
{
    /**
     * @param ArrayCollection<int, object> $items
     */
    public function run(ArrayCollection $items): void
    {
        $items->get(0);
    }

    public function ownGet(): void
    {
        // own method named get(), not a container
        $this->get('key');
    }

    public function get(string $key): string
    {
        return $key;
    }
}
