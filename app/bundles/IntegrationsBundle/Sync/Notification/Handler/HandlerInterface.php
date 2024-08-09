<?php

declare(strict_types=1);

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
