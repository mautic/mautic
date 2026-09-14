<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

final class DeclaredArrayShapeReturn
{
    /**
     * @return array{campaignLogCounts: int, campaignLogCountsProcessed: int}
     */
    public function twoKeyedValues(): array
    {
        return [
            'campaignLogCounts'          => 1,
            'campaignLogCountsProcessed' => 2,
        ];
    }

    /**
     * @return array{first: int, second: int, third: int}
     */
    public function threeKeyedValues(): array
    {
        return [
            'first'  => 1,
            'second' => 2,
            'third'  => 3,
        ];
    }
}
