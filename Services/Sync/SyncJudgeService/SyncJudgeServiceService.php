<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticIntegrationsBundle\Services\Sync\SyncJudgeService;

use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\InformationChangeRequestDAO;

/**
 * Class SyncJudgeServiceService.
 */
final class SyncJudgeServiceService implements SyncJudgeServiceInterface
{
    /**
     * @param string                             $mode
     * @param InformationChangeRequestDAO|null   $changeRequest1
     * @param InformationChangeRequestDAO|null   $changeRequest2
     *
     * @return mixed
     *
     * @throws \LogicException if conflict was not resolved
     */
    public function adjudicate(
        $mode = self::PRESUMPTION_OF_INNOCENCE_MODE,
        InformationChangeRequestDAO $changeRequest1 = null,
        InformationChangeRequestDAO $changeRequest2 = null
    )
    {
        if($changeRequest1 !== null && $changeRequest2 === null) {
            return $changeRequest1->getNewValue();
        }
        elseif ($changeRequest1 === null && $changeRequest2 !== null) {
            return $changeRequest2->getNewValue();
        }
        if ($changeRequest1->getNewValue() === $changeRequest2->getNewValue()) {
            return $changeRequest1->getNewValue();
        }
        switch ($mode) {
            case SyncJudgeServiceInterface::PRESUMPTION_OF_INNOCENCE_MODE:
                return $this->adjudicatePresumptionOfInnocence($changeRequest1, $changeRequest2);
            case SyncJudgeServiceInterface::HARD_EVIDENCE_MODE:
                return $this->adjudicateHardEvidence($changeRequest1, $changeRequest2);
            case SyncJudgeServiceInterface::BEST_EVIDENCE_MODE:
                return $this->adjudicateBestEvidence($changeRequest1, $changeRequest2);
            default:
                return $this->adjudicatePresumptionOfInnocence($changeRequest1, $changeRequest2);
        }
    }

    /**
     * @param InformationChangeRequestDAO $changeRequest1
     * @param InformationChangeRequestDAO $changeRequest2
     *
     * @return mixed
     */
    private function adjudicatePresumptionOfInnocence(InformationChangeRequestDAO $changeRequest1, InformationChangeRequestDAO $changeRequest2)
    {
        $certainChangeCompare = $this->compareTimestamps($changeRequest1->getCertainChangeTimestamp(), $changeRequest2->getCertainChangeTimestamp());
        if ($certainChangeCompare === 0) {
            $possibleChangeCompare = $this->compareTimestamps($changeRequest1->getPossibleChangeTimestamp(), $changeRequest2->getPossibleChangeTimestamp());
            if ($possibleChangeCompare === 0) {
                throw new \LogicException('Not resolved conflict');
            } elseif ($possibleChangeCompare === -1) {
                return $changeRequest1->getNewValue();
            } elseif ($possibleChangeCompare === 1) {
                return $changeRequest2->getNewValue();
            }
        } elseif ($certainChangeCompare === -1) {
            if ($changeRequest2->getPossibleChangeTimestamp() > $changeRequest1->getCertainChangeTimestamp()) {
                throw new \LogicException('Not resolved conflict');
            } else {
                return $changeRequest1->getNewValue();
            }
        } elseif ($certainChangeCompare === 1) {
            if ($changeRequest1->getPossibleChangeTimestamp() > $changeRequest2->getCertainChangeTimestamp()) {
                throw new \LogicException('Not resolved conflict');
            } else {
                return $changeRequest2->getNewValue();
            }
        }
    }

    /**
     * @param InformationChangeRequestDAO $changeRequest1
     * @param InformationChangeRequestDAO $changeRequest2
     *
     * @return mixed
     */
    private function adjudicateHardEvidence(InformationChangeRequestDAO $changeRequest1, InformationChangeRequestDAO $changeRequest2)
    {
        $certainChangeCompare = $this->compareTimestamps($changeRequest1->getCertainChangeTimestamp(), $changeRequest2->getCertainChangeTimestamp());
        if ($certainChangeCompare === 0) {
            throw new \LogicException('Not resolved conflict');
        } elseif ($certainChangeCompare === -1) {
            return $changeRequest1->getNewValue();
        } elseif ($certainChangeCompare === 1) {
            return $changeRequest2->getNewValue();
        }
    }

    /**
     * @param InformationChangeRequestDAO $changeRequest1
     * @param InformationChangeRequestDAO $changeRequest2
     *
     * @return mixed
     */
    private function adjudicateBestEvidence(InformationChangeRequestDAO $changeRequest1, InformationChangeRequestDAO $changeRequest2)
    {
        $certainChangeCompare = $this->compareTimestamps($changeRequest1->getCertainChangeTimestamp(), $changeRequest2->getCertainChangeTimestamp());
        if ($certainChangeCompare === 0) {
            $possibleChangeCompare = $this->compareTimestamps($changeRequest1->getPossibleChangeTimestamp(), $changeRequest2->getPossibleChangeTimestamp());
            if ($possibleChangeCompare === 0) {
                throw new \LogicException('Not resolved conflict');
            } elseif ($possibleChangeCompare === -1) {
                return $changeRequest1->getNewValue();
            } elseif ($possibleChangeCompare === 1) {
                return $changeRequest2->getNewValue();
            }
        } elseif ($certainChangeCompare === -1) {
            return $changeRequest1->getNewValue();
        } elseif ($certainChangeCompare === 1) {
            return $changeRequest2->getNewValue();
        }
    }

    /**
     * @param null $timestamp1
     * @param null $timestamp2
     *
     * @return int
     *
     * -1 if $timestamp1 > $timestamp2 or $timestamp1 is not null and $timestamp2 is
     * 1 if $timestamp2 > $timestamp1 or $timestamp2 is not null and $timestamp1 is
     * 0 if $timestamp1 === $timestamp2
     */
    private function compareTimestamps($timestamp1 = null, $timestamp2 = null)
    {
        if ($timestamp1 === $timestamp2) {
            return 0;
        }
        if ($timestamp1 !== null && ($timestamp2 === null || $timestamp1 > $timestamp2)) {
            return -1;
        }
        if ($timestamp2 !== null && ($timestamp1 === null || $timestamp2 > $timestamp1)) {
            return 1;
        }
    }
}
