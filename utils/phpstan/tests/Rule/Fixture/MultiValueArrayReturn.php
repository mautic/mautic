<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

final class MultiValueArrayReturn
{
    private const MONTH_FIRST = 'first';

    private const MONTH_LAST = 'last';

    public function nestedArrayValues(): array
    {
        return [
            'clel.campaign_id' => ['label' => 'a', 'type' => 'string'],
            'cmp.name'         => ['label' => 'b', 'type' => 'string'],
        ];
    }

    public function constantValues(): array
    {
        return [
            'mautic.report.schedule.month_frequency.first' => self::MONTH_FIRST,
            'mautic.report.schedule.month_frequency.last'  => self::MONTH_LAST,
        ];
    }

    public function twoKeyedValues(): array
    {
        return [
            'campaignLogCounts'          => 1,
            'campaignLogCountsProcessed' => 2,
        ];
    }

    public function threeKeyedValues(): array
    {
        return [
            'first'  => 1,
            'second' => 2,
            'third'  => 3,
        ];
    }

    public function positionalTuple(): array
    {
        $entities   = [];
        $totalCount = 0;

        return [$entities, $totalCount];
    }

    public function mixedKeys(): array
    {
        return ['first' => 1, 2];
    }

    public function singleKeyedValue(): array
    {
        return ['only' => 1];
    }

    public function fourKeyedValues(): array
    {
        return ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4];
    }

    public function notAnArray(): int
    {
        return 5;
    }

    public function returnInsideClosure(): void
    {
        $callback = function (): array {
            return ['oldPrimary' => 1, 'newPrimary' => 2];
        };
    }
}

function twoKeyedValuesInFunction(): array
{
    return ['a' => 1, 'b' => 2];
}
