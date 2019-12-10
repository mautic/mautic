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

namespace MauticPlugin\IntegrationsBundle\Sync\SyncJudge\Modes;

use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\InformationChangeRequestDAO;

interface JudgementModeInterface
{
    /**
     * @param InformationChangeRequestDAO $leftChangeRequest
     * @param InformationChangeRequestDAO $rightChangeRequest
     *
     * @return InformationChangeRequestDAO
     */
    public static function adjudicate(
        InformationChangeRequestDAO $leftChangeRequest,
        InformationChangeRequestDAO $rightChangeRequest
    ): InformationChangeRequestDAO;
}
