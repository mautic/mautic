<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\SyncJudge\Modes;

use Mautic\IntegrationsBundle\Sync\DAO\Sync\InformationChangeRequestDAO;
use Mautic\IntegrationsBundle\Sync\Exception\ConflictUnresolvedException;

class FuzzyEvidence implements JudgementModeInterface
{
    /**
     * @throws ConflictUnresolvedException
     */
    public static function adjudicate(
        InformationChangeRequestDAO $leftChangeRequest,
        InformationChangeRequestDAO $rightChangeRequest
    ): InformationChangeRequestDAO {
        try {
            return BestEvidence::adjudicate($leftChangeRequest, $rightChangeRequest);
        } catch (ConflictUnresolvedException) {
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
