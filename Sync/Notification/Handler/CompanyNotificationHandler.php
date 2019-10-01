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
use MauticPlugin\IntegrationsBundle\Sync\Notification\Helper\CompanyHelper;
use MauticPlugin\IntegrationsBundle\Sync\Notification\Helper\UserNotificationHelper;
use MauticPlugin\IntegrationsBundle\Sync\Notification\Writer;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;

class CompanyNotificationHandler implements HandlerInterface
{
    /**
     * @var Writer
     */
    private $writer;

    /**
     * @var UserNotificationHelper
     */
    private $userNotificationHelper;

    /**
     * @var CompanyHelper
     */
    private $companyHelper;

    /**
     * @param Writer                 $writer
     * @param UserNotificationHelper $userNotificationHelper
     * @param CompanyHelper          $companyHelper
     */
    public function __construct(Writer $writer, UserNotificationHelper $userNotificationHelper, CompanyHelper $companyHelper)
    {
        $this->writer                 = $writer;
        $this->userNotificationHelper = $userNotificationHelper;
        $this->companyHelper          = $companyHelper;
    }

    /**
     * @return string
     */
    public function getIntegration(): string
    {
        return MauticSyncDataExchange::NAME;
    }

    /**
     * @return string
     */
    public function getSupportedObject(): string
    {
        return MauticSyncDataExchange::OBJECT_COMPANY;
    }

    /**
     * @param NotificationDAO $notificationDAO
     * @param string          $integrationDisplayName
     * @param string          $objectDisplayName
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \MauticPlugin\IntegrationsBundle\Sync\Exception\ObjectNotSupportedException
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
