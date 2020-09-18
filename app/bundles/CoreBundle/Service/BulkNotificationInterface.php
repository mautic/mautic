<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Service;

use DateTime;
use Mautic\UserBundle\Entity\User;

interface BulkNotificationInterface
{
    public function addNotification(
        string $deduplicateValue,
        string $message,
        string $type = null,
        string $header = null,
        string $iconClass = null,
        DateTime $datetime = null,
        User $user = null
    ): void;

    public function flush(DateTime $deduplicateDateTimeFrom = null): void;
}
