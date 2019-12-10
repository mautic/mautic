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

class HardEvidence implements JudgementModeInterface
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
        if (null === $leftChangeRequest->getCertainChangeDateTime() || null === $rightChangeRequest->getCertainChangeDateTime()) {
            throw new ConflictUnresolvedException();
        }

        $certainChangeCompare = self::compareDateTimes(
            $leftChangeRequest->getCertainChangeDateTime(),
            $rightChangeRequest->getCertainChangeDateTime()
        );

        if (SyncJudgeInterface::NO_WINNER === $certainChangeCompare) {
            throw new ConflictUnresolvedException();
        }

        if (SyncJudgeInterface::LEFT_WINNER === $certainChangeCompare) {
            return $leftChangeRequest;
        }

        return $rightChangeRequest;
    }
}
