<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticIntegrationsBundle\Helpers\SyncJudgeService;

use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\InformationChangeRequestDAO;

/**
 * Class SyncJudge
 * @package MauticPlugin\MauticIntegrationsBundle\Services\SyncJudge
 */
final class SyncJudge implements SyncJudgeInterface
{
    const LEF_WINNER = 'left';
    const RIGHT_WINNER = 'right';
    const NO_WINNER = 'no';

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
            case SyncJudgeInterface::PRESUMPTION_OF_INNOCENCE_MODE:
                return $this->adjudicatePresumptionOfInnocence($changeRequest1, $changeRequest2);
            case SyncJudgeInterface::HARD_EVIDENCE_MODE:
                return $this->adjudicateHardEvidence($changeRequest1, $changeRequest2);
            case SyncJudgeInterface::BEST_EVIDENCE_MODE:
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
        if ($certainChangeCompare === self::NO_WINNER) {
            $possibleChangeCompare = $this->compareTimestamps($changeRequest1->getPossibleChangeTimestamp(), $changeRequest2->getPossibleChangeTimestamp());
            if ($possibleChangeCompare === self::NO_WINNER) {
                throw new \LogicException('Not resolved conflict');
            } elseif ($possibleChangeCompare === self::LEF_WINNER) {
                return $changeRequest1->getNewValue();
            } else {
                return $changeRequest2->getNewValue();
            }
        } elseif ($certainChangeCompare === self::LEF_WINNER) {
            if ($changeRequest2->getPossibleChangeTimestamp() > $changeRequest1->getCertainChangeTimestamp()) {
                throw new \LogicException('Not resolved conflict');
            } else {
                return $changeRequest1->getNewValue();
            }
        } else {
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
        if ($certainChangeCompare === self::NO_WINNER) {
            throw new \LogicException('Not resolved conflict');
        } elseif ($certainChangeCompare === self::LEF_WINNER) {
            return $changeRequest1->getNewValue();
        } else {
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
        if ($certainChangeCompare === self::NO_WINNER) {
            $possibleChangeCompare = $this->compareTimestamps($changeRequest1->getPossibleChangeTimestamp(), $changeRequest2->getPossibleChangeTimestamp());
            if ($possibleChangeCompare === self::NO_WINNER) {
                throw new \LogicException('Not resolved conflict');
            } elseif ($possibleChangeCompare === self::LEF_WINNER) {
                return $changeRequest1->getNewValue();
            } else {
                return $changeRequest2->getNewValue();
            }
        } elseif ($certainChangeCompare === self::LEF_WINNER) {
            return $changeRequest1->getNewValue();
        } else {
            return $changeRequest2->getNewValue();
        }
    }

    /**
     * @param int|null $timestamp1
     * @param int|null $timestamp2
     *
     * @return string self::LEFT_WINNER|self::RIGHT_WINNER|self::NO_WINNER
     */
    private function compareTimestamps($timestamp1 = null, $timestamp2 = null)
    {
        if ($timestamp1 !== null && ($timestamp2 === null || $timestamp1 > $timestamp2)) {
            return self::LEF_WINNER;
        }
        if ($timestamp2 !== null && ($timestamp1 === null || $timestamp2 > $timestamp1)) {
            return self::RIGHT_WINNER;
        }
        return self::NO_WINNER;
    }
}
