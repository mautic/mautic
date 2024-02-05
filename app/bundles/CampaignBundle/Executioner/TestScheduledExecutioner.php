<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Executioner;

/**
 * @internal Used in tests
 */
class TestScheduledExecutioner extends ScheduledExecutioner
{
    /**
     * @internal Used in tests
     */
    public function setNowTime(\DateTime $dateTime): void
    {
        $this->now = $dateTime;
    }
}
