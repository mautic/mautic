<?php

declare(strict_types=1);

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Event;

final class DeleteEvent extends \Symfony\Component\EventDispatcher\Event
{
    /**
     * @var int[]
     */
    private $eventIds;

    public function __construct(array $eventIds)
    {
        $this->eventIds   = $eventIds;
    }

    public function getEventIds(): array
    {
        return $this->eventIds;
    }
}
