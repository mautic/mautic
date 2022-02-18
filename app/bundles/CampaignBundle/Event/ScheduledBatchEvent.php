<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Event;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor;

class ScheduledBatchEvent extends AbstractLogCollectionEvent
{
    /**
     * @var bool
     */
    private $isReschedule;

    /**
     * ScheduledBatchEvent constructor.
     *
     * @param bool $isReschedule
     */
    public function __construct(AbstractEventAccessor $config, Event $event, Collection $logs, $isReschedule = false)
    {
        parent::__construct($config, $event, $logs);

        $this->isReschedule = $isReschedule;
    }

    /**
     * @return ArrayCollection<int,LeadEventLog>|Collection<int,LeadEventLog>
     */
    public function getScheduled()
    {
        return $this->logs;
    }

    /**
     * @return bool
     */
    public function isReschedule()
    {
        return $this->isReschedule;
    }
}
