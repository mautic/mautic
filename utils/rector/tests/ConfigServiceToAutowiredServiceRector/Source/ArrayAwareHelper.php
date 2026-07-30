<?php

declare(strict_types=1);

namespace Utils\Rector\Tests\ConfigServiceToAutowiredServiceRector\Source;

final class ArrayAwareHelper
{
    /**
     * @param array<string, string> $attributes
     */
    public function __construct(
        private array $attributes,
    ) {
    }
}
