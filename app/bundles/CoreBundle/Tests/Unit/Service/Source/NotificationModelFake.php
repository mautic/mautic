<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Service\Source;

use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\UserBundle\Entity\User;

final class NotificationModelFake extends NotificationModel
{
    /**
     * @var mixed[]
     */
    public array $notifications = [];

    /**
     * @noinspection PhpMissingParentConstructorInspection
     */
    public function __construct()
    {
    }

    public function addNotification($message, $type = null, bool $isRead = false, $header = null, $iconClass = null, ?\DateTime $datetime = null, ?User $user = null, ?string $deduplicateValue = null, ?\DateTime $deduplicateDateTimeFrom = null): void
    {
        $this->notifications[] = func_get_args();
    }
}
