<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Sync\SyncService;

/**
 * Interface SyncServiceInterface
 */
interface SyncServiceInterface
{
    /**
     * @param string                  $integration
     * @param bool                    $firstTimeSync
     * @param \DateTimeInterface|null $syncFromDateTime
     * @param \DateTimeInterface|null $syncToDateTime
     */
    public function processIntegrationSync(
        string $integration,
        $firstTimeSync,
        \DateTimeInterface $syncFromDateTime = null,
        \DateTimeInterface $syncToDateTime = null
    );
}
