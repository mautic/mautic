<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Sync\SyncJudge;

use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\InformationChangeRequestDAO;

/**
 * Class SyncJudge
 */
final class SyncJudge implements SyncJudgeInterface
{
    const LEF_WINNER = 'left';
    const RIGHT_WINNER = 'right';
    const NO_WINNER = 'no';

    /**
     * @param string $mode
     * @param InformationChangeRequestDAO|null $changeRequest1
     * @param InformationChangeRequestDAO|null $changeRequest2
     *
     * @return InformationChangeRequestDAO
     *
     * @throws \LogicException if conflict was not resolved
     */
    public function adjudicate(
        $mode = self::PRESUMPTION_OF_INNOCENCE_MODE,
        InformationChangeRequestDAO $changeRequest1 = null,
        InformationChangeRequestDAO $changeRequest2 = null
    ) {
        if ($changeRequest1 !== null && $changeRequest2 === null) {
            return $changeRequest1;
        }

        if ($changeRequest1 === null && $changeRequest2 !== null) {
            return $changeRequest2;
        }

        if ($changeRequest1->getNewValue() === $changeRequest2->getNewValue()) {
            return $changeRequest1;
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
     * @return InformationChangeRequestDAO
     */
    private function adjudicatePresumptionOfInnocence(InformationChangeRequestDAO $changeRequest1, InformationChangeRequestDAO $changeRequest2)
    {
        $certainChangeCompare = $this->compareDateTimes($changeRequest1->getCertainChangeDateTime(), $changeRequest2->getCertainChangeDateTime());
        if ($certainChangeCompare === self::NO_WINNER) {
            $possibleChangeCompare = $this->compareDateTimes(
                $changeRequest1->getPossibleChangeDateTime(),
                $changeRequest2->getPossibleChangeDateTime()
            );

            if ($possibleChangeCompare === self::NO_WINNER) {
                throw new \LogicException('Not resolved conflict');
            }

            if ($possibleChangeCompare === self::LEF_WINNER) {
                return $changeRequest1;
            }

            return $changeRequest2;
        }

        if ($certainChangeCompare === self::LEF_WINNER) {
            if ($changeRequest2->getPossibleChangeDateTime() > $changeRequest1->getCertainChangeDateTime()) {
                throw new \LogicException('Not resolved conflict');
            }

            return $changeRequest1;
        }

        if ($changeRequest1->getPossibleChangeDateTime() > $changeRequest2->getCertainChangeDateTime()) {
            throw new \LogicException('Not resolved conflict');
        }

        return $changeRequest2;
    }

    /**
     * @param InformationChangeRequestDAO $changeRequest1
     * @param InformationChangeRequestDAO $changeRequest2
     *
     * @return InformationChangeRequestDAO
     */
    private function adjudicateHardEvidence(InformationChangeRequestDAO $changeRequest1, InformationChangeRequestDAO $changeRequest2)
    {
        $certainChangeCompare = $this->compareDateTimes($changeRequest1->getCertainChangeDateTime(), $changeRequest2->getCertainChangeDateTime());
        if ($certainChangeCompare === self::NO_WINNER) {
            throw new \LogicException('Not resolved conflict');
        }

        if ($certainChangeCompare === self::LEF_WINNER) {
            return $changeRequest1;
        }

        return $changeRequest2;
    }

    /**
     * @param InformationChangeRequestDAO $changeRequest1
     * @param InformationChangeRequestDAO $changeRequest2
     *
     * @return InformationChangeRequestDAO
     */
    private function adjudicateBestEvidence(InformationChangeRequestDAO $changeRequest1, InformationChangeRequestDAO $changeRequest2)
    {
        $certainChangeCompare = $this->compareDateTimes($changeRequest1->getCertainChangeDateTime(), $changeRequest2->getCertainChangeDateTime());
        if ($certainChangeCompare === self::NO_WINNER) {
            $possibleChangeCompare = $this->compareDateTimes(
                $changeRequest1->getPossibleChangeDateTime(),
                $changeRequest2->getPossibleChangeDateTime()
            );

            if ($possibleChangeCompare === self::NO_WINNER) {
                throw new \LogicException('Not resolved conflict');
            }

            if ($possibleChangeCompare === self::LEF_WINNER) {
                return $changeRequest1;
            }

            return $changeRequest2;
        }

        if ($certainChangeCompare === self::LEF_WINNER) {
            return $changeRequest1;
        }

        return $changeRequest2;
    }

    /**
     * @param int|null $timestamp1
     * @param int|null $timestamp2
     *
     * @return string self::LEFT_WINNER|self::RIGHT_WINNER|self::NO_WINNER
     */
    private function compareDateTimes($timestamp1 = null, $timestamp2 = null)
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
