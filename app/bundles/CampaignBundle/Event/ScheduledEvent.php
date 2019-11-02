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

use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor;
use Symfony\Component\EventDispatcher\Event;

class ScheduledEvent extends Event
{
    use ContextTrait;

    /**
     * @var AbstractEventAccessor
     */
    private $eventConfig;

    /**
     * @var LeadEventLog
     */
    private $eventLog;

    /**
     * @var bool
     */
    private $isReschedule;

    /**
     * ScheduledEvent constructor.
     *
     * @param AbstractEventAccessor $config
     * @param LeadEventLog          $log
     * @param bool                  $isReschedule
     */
    public function __construct(AbstractEventAccessor $config, LeadEventLog $log, $isReschedule = false)
    {
        $this->eventConfig  = $config;
        $this->eventLog     = $log;
        $this->isReschedule = $isReschedule;
    }

    /**
     * @return AbstractEventAccessor
     */
    public function getEventConfig()
    {
        return $this->eventConfig;
    }

    /**
     * @return LeadEventLog
     */
    public function getLog()
    {
        return $this->eventLog;
    }

    /**
     * @return bool
     */
    public function isReschedule()
    {
        return $this->isReschedule;
    }
}
