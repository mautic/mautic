<?php

namespace Mautic\PluginBundle\Model\Sync;

/**
 * Interface FieldSyncJudgeInterface.
 */
interface FieldSyncJudgeInterface
{
    const PRESUMPTION_OF_INNOCENCE_MODE = 'presumptionOfInnocence';
    const HARD_EVIDENCE_MODE            = 'hardEvidence';

    /**
     * @param FieldSyncJudgeInterface::*_MODE $mode
     * @param FieldVindication                $claimant1
     * @param FieldVindication                $claimant2
     *
     * @return mixed New value of field
     */
    public function adjudicate($mode = self::PRESUMPTION_OF_INNOCENCE_MODE, FieldVindication $claimant1, FieldVindication $claimant2);
}
