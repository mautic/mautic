<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Sync\SyncJudge\Modes;

use MauticPlugin\IntegrationsBundle\Sync\SyncJudge\SyncJudgeInterface;

trait DateComparisonTrait
{
    /**
     * @param \DateTimeInterface|null $leftDateTime
     * @param \DateTimeInterface|null $rightDateTime
     *
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
