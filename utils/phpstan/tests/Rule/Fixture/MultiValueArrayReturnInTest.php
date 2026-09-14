<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

final class MultiValueArrayReturnInTest
{
    public function twoKeyedValues(): array
    {
        return [
            'campaignLogCounts'          => 1,
            'campaignLogCountsProcessed' => 2,
        ];
    }
}
