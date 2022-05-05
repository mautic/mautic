<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\SyncJudge\Modes;

use Mautic\IntegrationsBundle\Sync\DAO\Sync\InformationChangeRequestDAO;
use Mautic\IntegrationsBundle\Sync\Exception\ConflictUnresolvedException;
use Mautic\IntegrationsBundle\Sync\SyncJudge\SyncJudgeInterface;

class HardEvidence implements JudgementModeInterface
{
    use DateComparisonTrait;

    /**
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
