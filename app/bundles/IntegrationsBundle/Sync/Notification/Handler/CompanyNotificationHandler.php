<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\Notification\Handler;

use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\NotificationDAO;
use Mautic\IntegrationsBundle\Sync\Notification\Helper\CompanyHelper;
use Mautic\IntegrationsBundle\Sync\Notification\Helper\UserNotificationHelper;
use Mautic\IntegrationsBundle\Sync\Notification\Writer;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;

class CompanyNotificationHandler implements HandlerInterface
{
    public function __construct(
        private Writer $writer,
        private UserNotificationHelper $userNotificationHelper,
        private CompanyHelper $companyHelper,
    ) {
    }

    public function getIntegration(): string
    {
        return MauticSyncDataExchange::NAME;
    }

    public function getSupportedObject(): string
    {
        return MauticSyncDataExchange::OBJECT_COMPANY;
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Mautic\IntegrationsBundle\Sync\Exception\ObjectNotSupportedException
     */
    public function writeEntry(NotificationDAO $notificationDAO, string $integrationDisplayName, string $objectDisplayName): void
    {
        $this->writer->writeAuditLogEntry(
            $notificationDAO->getIntegration(),
            $notificationDAO->getMauticObject(),
            $notificationDAO->getMauticObjectId(),
            'sync',
            [
                'integrationObject'   => $notificationDAO->getIntegrationObject(),
                'integrationObjectId' => $notificationDAO->getIntegrationObjectId(),
                'message'             => $notificationDAO->getMessage(),
            ]
        );

        $this->userNotificationHelper->writeNotification(
            $notificationDAO->getMessage(),
            $integrationDisplayName,
            $objectDisplayName,
            $notificationDAO->getMauticObject(),
            $notificationDAO->getMauticObjectId(),
            (string) $this->companyHelper->getCompanyName($notificationDAO->getMauticObjectId())
        );
    }

    public function finalize(): void
    {
        // Nothing to do
    }
}
