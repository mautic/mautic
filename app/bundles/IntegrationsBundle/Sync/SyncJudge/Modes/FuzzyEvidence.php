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

class FuzzyEvidence implements JudgementModeInterface
{
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
            return BestEvidence::adjudicate($leftChangeRequest, $rightChangeRequest);
        } catch (ConflictUnresolvedException $exception) {
        }

        if (
            $leftChangeRequest->getCertainChangeDateTime() &&
            $rightChangeRequest->getPossibleChangeDateTime() &&
            $leftChangeRequest->getCertainChangeDateTime() > $rightChangeRequest->getPossibleChangeDateTime()
        ) {
            return $leftChangeRequest;
        }

        if (
            $rightChangeRequest->getCertainChangeDateTime() &&
            $leftChangeRequest->getPossibleChangeDateTime() &&
            $rightChangeRequest->getCertainChangeDateTime() > $leftChangeRequest->getPossibleChangeDateTime()
        ) {
            return $rightChangeRequest;
        }

        throw new ConflictUnresolvedException();
    }
}
