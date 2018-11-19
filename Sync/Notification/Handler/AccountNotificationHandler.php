<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Sync\Notification\Handler;

use Mautic\UserBundle\Entity\User;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Order\NotificationDAO;
use MauticPlugin\IntegrationsBundle\Sync\Notification\Helper\UserNotificationHelper;
use MauticPlugin\IntegrationsBundle\Sync\Notification\Writer;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;
use Mautic\UserBundle\Entity\UserRepository;

class AccountNotificationHandler implements HandlerInterface
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
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @param Writer $writer
     * @param UserNotificationHelper $userNotificationHelper
     * @param UserRepository $userRepository
     */
    public function __construct(Writer $writer, UserNotificationHelper $userNotificationHelper, UserRepository $userRepository)
    {
        $this->writer = $writer;
        $this->userNotificationHelper = $userNotificationHelper;
        $this->userRepository = $userRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getIntegration(): string
    {
        return MauticSyncDataExchange::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedObject(): string
    {
        return MauticSyncDataExchange::OBJECT_ACCOUNT;
    }

    /**
     * {@inheritdoc}
     */
    public function writeEntry(NotificationDAO $notificationDAO, string $integrationDisplayName, string $objectDisplayName): void
    {
        $this->writer->writeAuditLogEntry(
            $notificationDAO->getIntegration(),
            $notificationDAO->getMauticObject(),
            $notificationDAO->getMauticObjectId(),
            'sync',
            [
                'integrationObject' => $notificationDAO->getIntegrationObject(),
                'integrationObjectId' => $notificationDAO->getIntegrationObjectId(),
                'message' => $notificationDAO->getMessage()
            ]
        );

        /** @var User $user */
        $user = $this->userRepository->findOneById($notificationDAO->getMauticObjectId());
        $name = $user->getFirstName() . ' ' . $user->getLastName();

        $this->userNotificationHelper->writeNotification(
            $notificationDAO->getMessage(),
            $integrationDisplayName,
            $objectDisplayName,
            $notificationDAO->getMauticObject(),
            $notificationDAO->getMauticObjectId(),
            $name
        );
    }

    /**
     * {@inheritdoc}
     */
    public function finalize(): void
    {
        // Nothing to do
    }
}