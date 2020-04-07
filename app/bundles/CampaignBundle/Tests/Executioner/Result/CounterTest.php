<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Tests\Executioner\Result;

use Mautic\CampaignBundle\Executioner\Result\Counter;

class CounterTest extends \PHPUnit\Framework\TestCase
{
    public function testCounterIncrements()
    {
        $counter = new Counter(1, 1, 1, 1, 1, 1);

        $counter->advanceEvaluated(2);
        $this->assertEquals(3, $counter->getEvaluated());
        $this->assertEquals(3, $counter->getTotalEvaluated());

        $counter->advanceTotalEvaluated(1);
        $this->assertEquals(3, $counter->getEvaluated());
        $this->assertEquals(4, $counter->getTotalEvaluated());

        $counter->advanceExecuted(2);
        $this->assertEquals(3, $counter->getExecuted());
        $this->assertEquals(3, $counter->getTotalExecuted());

        $counter->advanceTotalExecuted(1);
        $this->assertEquals(3, $counter->getExecuted());
        $this->assertEquals(4, $counter->getTotalExecuted());

        $counter->advanceTotalScheduled(2);
        $this->assertEquals(3, $counter->getTotalScheduled());
    }
}
