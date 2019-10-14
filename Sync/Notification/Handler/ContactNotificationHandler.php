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

use Doctrine\ORM\EntityManagerInterface;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Entity\LeadEventLogRepository;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Order\NotificationDAO;
use MauticPlugin\IntegrationsBundle\Sync\Notification\Helper\UserSummaryNotificationHelper;
use MauticPlugin\IntegrationsBundle\Sync\Notification\Writer;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;

class ContactNotificationHandler implements HandlerInterface
{
    /**
     * @var Writer
     */
    private $writer;

    /**
     * @var LeadEventLogRepository
     */
    private $leadEventRepository;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var UserSummaryNotificationHelper
     */
    private $userNotificationHelper;

    /**
     * @var string
     */
    private $integrationDisplayName;

    /**
     * @var string
     */
    private $objectDisplayName;

    /**
     * @param Writer                        $writer
     * @param LeadEventLogRepository        $leadEventRepository
     * @param EntityManagerInterface        $em
     * @param UserSummaryNotificationHelper $userNotificationHelper
     */
    public function __construct(
        Writer $writer,
        LeadEventLogRepository $leadEventRepository,
        EntityManagerInterface $em,
        UserSummaryNotificationHelper $userNotificationHelper
    ) {
        $this->writer                 = $writer;
        $this->leadEventRepository    = $leadEventRepository;
        $this->em                     = $em;
        $this->userNotificationHelper = $userNotificationHelper;
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
        return Contact::NAME;
    }

    /**
     * @param NotificationDAO $notificationDAO
     * @param string          $integrationDisplayName
     * @param string          $objectDisplayName
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public function writeEntry(NotificationDAO $notificationDAO, string $integrationDisplayName, string $objectDisplayName): void
    {
        $this->integrationDisplayName = $integrationDisplayName;
        $this->objectDisplayName      = $objectDisplayName;

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

        $this->writeEventLogEntry($notificationDAO->getIntegration(), $notificationDAO->getMauticObjectId(), $notificationDAO->getMessage());

        // Store these so we can send one notice to the user
        $this->userNotificationHelper->storeSummaryNotification($integrationDisplayName, $objectDisplayName, $notificationDAO->getMauticObjectId());
    }

    public function finalize(): void
    {
        $this->userNotificationHelper->writeNotifications(
            Contact::NAME,
            'mautic.integration.sync.user_notification.contact_message'
        );
    }

    /**
     * @param string $integration
     * @param int    $contactId
     * @param string $message
     *
     * @throws \Doctrine\ORM\ORMException
     */
    private function writeEventLogEntry(string $integration, int $contactId, string $message): void
    {
        $eventLog = new LeadEventLog();
        $eventLog
            ->setLead($this->em->getReference(Lead::class, $contactId))
            ->setBundle('integrations')
            ->setObject($integration)
            ->setAction('sync')
            ->setProperties(
                [
                    'message'     => $message,
                    'integration' => $this->integrationDisplayName,
                    'object'      => $this->objectDisplayName,
                ]
            );

        $this->leadEventRepository->saveEntity($eventLog);
        $this->leadEventRepository->detachEntity($eventLog);
    }
}
