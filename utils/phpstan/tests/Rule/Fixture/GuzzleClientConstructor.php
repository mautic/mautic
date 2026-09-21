<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

use GuzzleHttp\Client;

final class GuzzleClientConstructor
{
    public function __construct(
        private readonly Client $client,
    ) {
    }
}
