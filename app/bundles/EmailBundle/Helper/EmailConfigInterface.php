<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Helper;

interface EmailConfigInterface
{
    public function isDraftEnabled(): bool;

    /**
     * Days after sending that stored email personalisation is deleted, 0 when it is kept.
     */
    public function getStatsCompactionThresholdDays(): int;
}
