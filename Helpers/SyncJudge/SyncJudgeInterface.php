<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Helpers\SyncJudge;

use MauticPlugin\IntegrationsBundle\DAO\Sync\InformationChangeRequestDAO;

/**
 * Interface SyncJudgeInterface
 */
interface SyncJudgeInterface
{
    /**
     * Winner is selected only if provided vindications don't leave open possibilities of different result.
     */
    const PRESUMPTION_OF_INNOCENCE_MODE = 'presumptionOfInnocence';

    /**
     * Winner is selected based on certain information only.
     */
    const HARD_EVIDENCE_MODE            = 'hardEvidence';

    /**
     * Winner is selected based on best evidence available.
     */
    const BEST_EVIDENCE_MODE            = 'bestEvidence';

    /**
     * @param string $mode
     * @param InformationChangeRequestDAO|null $changeRequest1
     * @param InformationChangeRequestDAO|null $changeRequest2
     *
     * @return InformationChangeRequestDAO
     */
    public function adjudicate(
        $mode = self::PRESUMPTION_OF_INNOCENCE_MODE,
        InformationChangeRequestDAO $changeRequest1 = null,
        InformationChangeRequestDAO $changeRequest2 = null
    );
}
