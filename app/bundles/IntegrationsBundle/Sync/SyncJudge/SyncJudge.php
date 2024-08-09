<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\SyncJudge;

use Mautic\IntegrationsBundle\Sync\DAO\Sync\InformationChangeRequestDAO;
use Mautic\IntegrationsBundle\Sync\Exception\ConflictUnresolvedException;
use Mautic\IntegrationsBundle\Sync\SyncJudge\Modes\BestEvidence;
use Mautic\IntegrationsBundle\Sync\SyncJudge\Modes\FuzzyEvidence;
use Mautic\IntegrationsBundle\Sync\SyncJudge\Modes\HardEvidence;

final class SyncJudge implements SyncJudgeInterface
{
    /**
     * @param string $mode
     *
     * @return InformationChangeRequestDAO
     *
     * @throws ConflictUnresolvedException
     */
    public function adjudicate(
        $mode,
        InformationChangeRequestDAO $leftChangeRequest,
        InformationChangeRequestDAO $rightChangeRequest
    ) {
        if ($leftChangeRequest->getNewValue() === $rightChangeRequest->getNewValue()) {
            return $leftChangeRequest;
        }

        return match ($mode) {
            SyncJudgeInterface::HARD_EVIDENCE_MODE => HardEvidence::adjudicate($leftChangeRequest, $rightChangeRequest),
            SyncJudgeInterface::BEST_EVIDENCE_MODE => BestEvidence::adjudicate($leftChangeRequest, $rightChangeRequest),
            default                                => FuzzyEvidence::adjudicate($leftChangeRequest, $rightChangeRequest),
        };
    }
}
