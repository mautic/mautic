<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Executioner;

use Symfony\Contracts\Service\ResetInterface;

/**
 * @internal Used in tests
 */
class TestInactiveExecutioner extends InactiveExecutioner implements ResetInterface
{
    /**
     * @internal Used in tests
     */
    public function setNowTime(\DateTime $dateTime): void
    {
        $this->now = $dateTime;
    }

    public function reset(): void
    {
        $this->now = null;
    }
}
