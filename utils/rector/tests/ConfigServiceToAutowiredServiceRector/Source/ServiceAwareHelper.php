<?php

declare(strict_types=1);

namespace Utils\Rector\Tests\ConfigServiceToAutowiredServiceRector\Source;

final class ServiceAwareHelper
{
    public function __construct(
        private mixed $someService,
    ) {
    }
}
