<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\Model\Sync;

/**
 * Class FieldSyncJudge.
 */
final class FieldSyncJudge implements FieldSyncJudgeInterface
{
    /**
     * @param string           $mode
     * @param FieldVindication $claimant1
     * @param FieldVindication $claimant2
     *
     * @return mixed
     *
     * @throws \LogicException if conflict was not resolved
     */
    public function adjudicate($mode = self::PRESUMPTION_OF_INNOCENCE_MODE, FieldVindication $claimant1, FieldVindication $claimant2)
    {
        if ($claimant1->getValue() === $claimant2->getValue()) {
            return $claimant1->getValue();
        }
        switch ($mode) {
            case FieldSyncJudgeInterface::PRESUMPTION_OF_INNOCENCE_MODE:
                return $this->adjudicatePresumptionOfInnocence($claimant1, $claimant2);
            case FieldSyncJudgeInterface::HARD_EVIDENCE_MODE:
                return $this->adjudicateHardEvidence($claimant1, $claimant2);
            default:
                return $this->adjudicatePresumptionOfInnocence($claimant1, $claimant2);
        }
    }

    /**
     * @param FieldVindication $claimant1
     * @param FieldVindication $claimant2
     *
     * @return mixed
     */
    private function adjudicatePresumptionOfInnocence(FieldVindication $claimant1, FieldVindication $claimant2)
    {
        $certainChangeCompare = $this->compareTimestamps($claimant1->getCertainChangeTimestamp(), $claimant2->getCertainChangeTimestamp());
        if ($certainChangeCompare === 0) {
            $possibleChangeCompare = $this->compareTimestamps($claimant1->getPossibleChangeTimestamp(), $claimant2->getPossibleChangeTimestamp());
            if ($possibleChangeCompare === 0) {
                throw new \LogicException('Not resolved conflict');
            } elseif ($possibleChangeCompare === -1) {
                return $claimant1->getValue();
            } elseif ($possibleChangeCompare === 1) {
                return $claimant2->getValue();
            }
        } elseif ($certainChangeCompare === -1) {
            if ($claimant2->getPossibleChangeTimestamp() > $claimant1->getCertainChangeTimestamp()) {
                throw new \LogicException('Not resolved conflict');
            } else {
                return $claimant1->getValue();
            }
        } elseif ($certainChangeCompare === 1) {
            if ($claimant1->getPossibleChangeTimestamp() > $claimant2->getCertainChangeTimestamp()) {
                throw new \LogicException('Not resolved conflict');
            } else {
                return $claimant2->getValue();
            }
        }
    }

    /**
     * @param FieldVindication $claimant1
     * @param FieldVindication $claimant2
     *
     * @return mixed
     */
    private function adjudicateHardEvidence(FieldVindication $claimant1, FieldVindication $claimant2)
    {
        $certainChangeCompare = $this->compareTimestamps($claimant1->getCertainChangeTimestamp(), $claimant2->getCertainChangeTimestamp());
        if ($certainChangeCompare === 0) {
            $possibleChangeCompare = $this->compareTimestamps($claimant1->getPossibleChangeTimestamp(), $claimant2->getPossibleChangeTimestamp());
            if ($possibleChangeCompare === 0) {
                throw new \LogicException('Not resolved conflict');
            } elseif ($possibleChangeCompare === -1) {
                return $claimant1->getValue();
            } elseif ($possibleChangeCompare === 1) {
                return $claimant2->getValue();
            }
        } elseif ($certainChangeCompare === -1) {
            return $claimant1->getValue();
        } elseif ($certainChangeCompare === 1) {
            return $claimant2->getValue();
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
