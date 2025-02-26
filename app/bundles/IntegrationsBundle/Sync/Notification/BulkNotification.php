<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\Notification;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Service\BulkNotificationInterface;
use Mautic\IntegrationsBundle\Sync\Notification\Helper\UserNotificationBuilder;
use Mautic\UserBundle\Entity\User;

class BulkNotification
{
    public function __construct(
        private BulkNotificationInterface $bulkNotification,
        private UserNotificationBuilder $userNotificationBuilder,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function addNotification(
        string $deduplicateValue,
        string $message,
        string $integrationDisplayName,
        string $objectDisplayName,
        string $mauticObject,
        int $id,
        string $linkText,
    ): void {
        $link    = $this->userNotificationBuilder->buildLink($mauticObject, $id, $linkText);
        $userIds = $this->userNotificationBuilder->getUserIds($mauticObject, $id);

        foreach ($userIds as $userId) {
            /** @var User $user */
            $user = $this->entityManager->getReference(User::class, $userId);
            $this->bulkNotification->addNotification(
                $deduplicateValue,
                $this->userNotificationBuilder->formatMessage($message, $link),
                null,
                $this->userNotificationBuilder->formatHeader($integrationDisplayName, $objectDisplayName),
                'ri-refresh-line',
                null,
                $user
            );
        }
    }

    /**
     * @param \DateTime|null $deduplicateDateTimeFrom If last 24 hours for deduplication does not fit, change it here
     */
    public function flush(\DateTime $deduplicateDateTimeFrom = null): void
    {
        $this->bulkNotification->flush($deduplicateDateTimeFrom);
    }
}
