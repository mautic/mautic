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

        switch ($mode) {
            case SyncJudgeInterface::HARD_EVIDENCE_MODE:
                return HardEvidence::adjudicate($leftChangeRequest, $rightChangeRequest);
            case SyncJudgeInterface::BEST_EVIDENCE_MODE:
                return BestEvidence::adjudicate($leftChangeRequest, $rightChangeRequest);
            default:
                return FuzzyEvidence::adjudicate($leftChangeRequest, $rightChangeRequest);
        }
    }
}
