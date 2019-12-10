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

use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\InformationChangeRequestDAO;
use MauticPlugin\IntegrationsBundle\Sync\Exception\ConflictUnresolvedException;
use MauticPlugin\IntegrationsBundle\Sync\SyncJudge\SyncJudgeInterface;

class BestEvidence implements JudgementModeInterface
{
    use DateComparisonTrait;

    /**
     * @param InformationChangeRequestDAO $leftChangeRequest
     * @param InformationChangeRequestDAO $rightChangeRequest
     *
     * @return InformationChangeRequestDAO
     *
     * @throws ConflictUnresolvedException
     */
    public static function adjudicate(
        InformationChangeRequestDAO $leftChangeRequest,
        InformationChangeRequestDAO $rightChangeRequest
    ): InformationChangeRequestDAO {
        try {
            return HardEvidence::adjudicate($leftChangeRequest, $rightChangeRequest);
        } catch (ConflictUnresolvedException $exception) {
        }

        if (null === $leftChangeRequest->getPossibleChangeDateTime() || null === $rightChangeRequest->getPossibleChangeDateTime()) {
            throw new ConflictUnresolvedException();
        }

        $possibleChangeCompare = self::compareDateTimes(
            $leftChangeRequest->getPossibleChangeDateTime(),
            $rightChangeRequest->getPossibleChangeDateTime()
        );

        if (SyncJudgeInterface::NO_WINNER === $possibleChangeCompare) {
            throw new ConflictUnresolvedException();
        }

        if (SyncJudgeInterface::LEFT_WINNER === $possibleChangeCompare) {
            return $leftChangeRequest;
        }

        return $rightChangeRequest;
    }
}
