<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Service;

use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\UserBundle\Entity\User;

final class BulkNotification implements BulkNotificationInterface
{
    /**
     * @var mixed[]
     */
    private array $notifications = [];

    public function __construct(
        private NotificationModel $notificationModel,
    ) {
    }

    public function addNotification(
        string $deduplicateValue,
        string $message,
        string $type = null,
        string $header = null,
        string $iconClass = null,
        \DateTime $datetime = null,
        User $user = null,
    ): void {
        if (isset($this->notifications[$deduplicateValue])) {
            return;
        }

        $this->notifications[$deduplicateValue] = [
            'message'   => $message,
            'type'      => $type,
            'header'    => $header,
            'iconClass' => $iconClass,
            'datetime'  => $datetime,
            'user'      => $user,
        ];
    }

    /**
     * @param \DateTime|null $deduplicateDateTimeFrom If last 24 hours for deduplication does not fit, change it here
     */
    public function flush(\DateTime $deduplicateDateTimeFrom = null): void
    {
        foreach ($this->notifications as $deduplicateValue => $data) {
            $this->notificationModel->addNotification(
                $data['message'],
                $data['type'],
                false,
                $data['header'],
                $data['iconClass'],
                $data['datetime'],
                $data['user'],
                $deduplicateValue,
                $deduplicateDateTimeFrom
            );
            unset($this->notifications[$deduplicateValue]);
        }
    }
}
