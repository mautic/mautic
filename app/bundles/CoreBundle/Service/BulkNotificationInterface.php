<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Service;

use Mautic\UserBundle\Entity\User;

interface BulkNotificationInterface
{
    public function addNotification(
        string $deduplicateValue,
        string $message,
        string $type = null,
        string $header = null,
        string $iconClass = null,
        \DateTime $datetime = null,
        User $user = null
    ): void;

    public function flush(\DateTime $deduplicateDateTimeFrom = null): void;
}
