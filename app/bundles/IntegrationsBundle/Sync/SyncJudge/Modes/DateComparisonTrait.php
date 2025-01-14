<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\SyncJudge\Modes;

use Mautic\IntegrationsBundle\Sync\SyncJudge\SyncJudgeInterface;

trait DateComparisonTrait
{
    /**
     * @return string self::LEFT_WINNER|self::RIGHT_WINNER|self::NO_WINNER
     */
    private static function compareDateTimes(?\DateTimeInterface $leftDateTime = null, ?\DateTimeInterface $rightDateTime = null)
    {
        if (null !== $leftDateTime && (null === $rightDateTime || $leftDateTime > $rightDateTime)) {
            return SyncJudgeInterface::LEFT_WINNER;
        }

        if (null !== $rightDateTime && (null === $leftDateTime || $rightDateTime > $leftDateTime)) {
            return SyncJudgeInterface::RIGHT_WINNER;
        }

        return SyncJudgeInterface::NO_WINNER;
    }
}
