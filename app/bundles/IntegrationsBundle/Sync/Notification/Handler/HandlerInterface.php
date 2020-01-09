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

namespace Mautic\IntegrationsBundle\Sync\Notification\Handler;

use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\NotificationDAO;

interface HandlerInterface
{
    public function getIntegration(): string;

    public function getSupportedObject(): string;

    public function writeEntry(NotificationDAO $notificationDAO, string $integrationDisplayName, string $objectDisplayName): void;

    /**
     * Finalize notifications such as pushing summary entries to the user notifications tray.
     */
    public function finalize(): void;
}
