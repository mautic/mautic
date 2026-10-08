<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Executioner\Result;

use Mautic\CampaignBundle\Executioner\Result\Counter;

final class CounterTest extends \PHPUnit\Framework\TestCase
{
    public function testCounterIncrements(): void
    {
        $counter = new Counter(1, 1, 1, 1, 1, 1);

        $counter->advanceEvaluated(2);
        $this->assertSame(3, $counter->getEvaluated());
        $this->assertSame(3, $counter->getTotalEvaluated());

        $counter->advanceTotalEvaluated(1);
        $this->assertSame(3, $counter->getEvaluated());
        $this->assertSame(4, $counter->getTotalEvaluated());

        $counter->advanceExecuted(2);
        $this->assertSame(3, $counter->getExecuted());
        $this->assertSame(3, $counter->getTotalExecuted());

        $counter->advanceTotalExecuted(1);
        $this->assertSame(3, $counter->getExecuted());
        $this->assertSame(4, $counter->getTotalExecuted());

        $counter->advanceTotalScheduled(2);
        $this->assertSame(3, $counter->getTotalScheduled());
    }
}
