<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\Notification\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\NotificationDAO;
use Mautic\IntegrationsBundle\Sync\Notification\Helper\UserSummaryNotificationHelper;
use Mautic\IntegrationsBundle\Sync\Notification\Writer;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Entity\LeadEventLogRepository;

class ContactNotificationHandler implements HandlerInterface
{
    private ?string $integrationDisplayName = null;

    private ?string $objectDisplayName = null;

    public function __construct(
        private Writer $writer,
        private LeadEventLogRepository $leadEventRepository,
        private EntityManagerInterface $em,
        private UserSummaryNotificationHelper $userNotificationHelper,
    ) {
    }

    public function getIntegration(): string
    {
        return MauticSyncDataExchange::NAME;
    }

    public function getSupportedObject(): string
    {
        return Contact::NAME;
    }

    /**
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
