<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\Notification;

use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Helper\ConfigIntegrationsHelper;
use Mautic\IntegrationsBundle\Helper\SyncIntegrationsHelper;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormSyncInterface;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\NotificationDAO;
use Mautic\IntegrationsBundle\Sync\Exception\HandlerNotSupportedException;
use Mautic\IntegrationsBundle\Sync\Notification\Handler\HandlerContainer;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;

class Notifier
{
    public function __construct(
        private HandlerContainer $handlerContainer,
        private SyncIntegrationsHelper $syncIntegrationsHelper,
        private ConfigIntegrationsHelper $configIntegrationsHelper
    ) {
    }

    /**
     * @param NotificationDAO[] $notifications
     * @param string            $integrationHandler
     *
     * @throws HandlerNotSupportedException
     * @throws IntegrationNotFoundException
     */
    public function noteMauticSyncIssue(array $notifications, $integrationHandler = MauticSyncDataExchange::NAME): void
    {
        foreach ($notifications as $notification) {
            $handler = $this->handlerContainer->getHandler($integrationHandler, $notification->getMauticObject());

            $integrationDisplayName = $this->syncIntegrationsHelper->getIntegration($notification->getIntegration())->getDisplayName();
            $objectDisplayName      = $this->getObjectDisplayName($notification->getIntegration(), $notification->getIntegrationObject());

            $handler->writeEntry($notification, $integrationDisplayName, $objectDisplayName);
        }
    }

    /**
     * Finalizes notifications such as pushing summary entries to the user notifications.
     */
    public function finalizeNotifications(): void
    {
        foreach ($this->handlerContainer->getHandlers() as $handler) {
            $handler->finalize();
        }
    }

    /**
     * @return string
     */
    private function getObjectDisplayName(string $integration, string $object)
    {
        try {
            $configIntegration = $this->configIntegrationsHelper->getIntegration($integration);
        } catch (IntegrationNotFoundException) {
            return ucfirst($object);
        }

        if (!$configIntegration instanceof ConfigFormSyncInterface) {
            return ucfirst($object);
        }

        $objects = $configIntegration->getSyncConfigObjects();

        if (!isset($objects[$object])) {
            return ucfirst($object);
        }

        return $objects[$object];
    }
}
