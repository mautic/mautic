<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\Notification\Helper;

use Doctrine\ORM\ORMException;
use Mautic\IntegrationsBundle\Sync\Exception\ObjectNotSupportedException;
use Mautic\IntegrationsBundle\Sync\Notification\Writer;

class UserNotificationHelper
{
    public function __construct(
        private Writer $writer,
        private UserNotificationBuilder $userNotificationBuilder
    ) {
    }

    /**
     * @throws ORMException
     * @throws ObjectNotSupportedException
     */
    public function writeNotification(
        string $message,
        string $integrationDisplayName,
        string $objectDisplayName,
        string $mauticObject,
        int $id,
        string $linkText,
        string $deduplicateValue = null,
        \DateTime $deduplicateDateTimeFrom = null
    ): void {
        $link    = $this->userNotificationBuilder->buildLink($mauticObject, $id, $linkText);
        $userIds = $this->userNotificationBuilder->getUserIds($mauticObject, $id);

        foreach ($userIds as $userId) {
            $this->writer->writeUserNotification(
                $this->userNotificationBuilder->formatHeader($integrationDisplayName, $objectDisplayName),
                $this->userNotificationBuilder->formatMessage($message, $link),
                $userId,
                $deduplicateValue,
                $deduplicateDateTimeFrom
            );
        }
    }
}
