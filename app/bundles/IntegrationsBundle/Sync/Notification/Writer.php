<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\Notification;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\UserBundle\Entity\User;

class Writer
{
    private \Mautic\CoreBundle\Model\NotificationModel $notificationModel;

    private \Mautic\CoreBundle\Model\AuditLogModel $auditLogModel;

    private \Doctrine\ORM\EntityManagerInterface $em;

    public function __construct(
        NotificationModel $notificationModel,
        AuditLogModel $auditLogModel,
        EntityManagerInterface $entityManager
    ) {
        $this->notificationModel   = $notificationModel;
        $this->auditLogModel       = $auditLogModel;
        $this->em                  = $entityManager;
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     */
    public function writeUserNotification(string $header, string $message, int $userId, string $deduplicateValue = null, \DateTime $deduplicateDateTimeFrom = null): void
    {
        $this->notificationModel->addNotification(
            $message,
            null,
            false,
            $header,
            'fa-refresh',
            null,
            $this->em->getReference(User::class, $userId),
            $deduplicateValue,
            $deduplicateDateTimeFrom
        );
    }

    public function writeAuditLogEntry(string $bundle, string $object, ?int $objectId, string $action, array $details): void
    {
        $log = [
            'bundle'   => $bundle,
            'object'   => $object,
            'objectId' => $objectId,
            'action'   => $action,
            'details'  => $details,
        ];

        $this->auditLogModel->writeToLog($log);
    }
}
