<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Services;

use Mautic\LeadBundle\Services\PeakInteractionTimer;

final class TestablePeakInteractionTimer extends PeakInteractionTimer
{
    private \DateTime $testTime;

    public function setCurrentDateTime(\DateTime $dateTime): void
    {
        $this->testTime = $dateTime;
    }

    protected function getCurrentDateTime(\DateTimeZone $timezone): \DateTime
    {
        return clone $this->testTime;
    }
}
