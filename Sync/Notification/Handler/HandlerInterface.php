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

namespace MauticPlugin\IntegrationsBundle\Sync\Notification\Handler;

use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Order\NotificationDAO;

interface HandlerInterface
{
    /**
     * @return string
     */
    public function getIntegration(): string;

    /**
     * @return string
     */
    public function getSupportedObject(): string;

    /**
     * @param NotificationDAO $notificationDAO
     * @param string          $integrationDisplayName
     * @param string          $objectDisplayName
     */
    public function writeEntry(NotificationDAO $notificationDAO, string $integrationDisplayName, string $objectDisplayName): void;

    /**
     * Finalize notifications such as pushing summary entries to the user notifications tray.
     */
    public function finalize(): void;
}
