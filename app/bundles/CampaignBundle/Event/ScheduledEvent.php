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
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor;

class ScheduledEvent extends CampaignScheduledEvent
{
    /*
     * @deprecated support for pre 2.13.0; to be removed in 3.0
     */
    use EventArrayTrait;

    /**
     * @var AbstractEventAccessor
     */
    private $config;

    /**
     * @var ArrayCollection
     */
    private $log;

    /**
     * PendingEvent constructor.
     *
     * @param AbstractEventAccessor $config
     * @param Event                 $event
     * @param ArrayCollection       $log
     */
    public function __construct(AbstractEventAccessor $config, LeadEventLog $log)
    {
        $this->config = $config;
        $this->log    = $log;

        // @deprecated support for pre 2.13.0; to be removed in 3.0
        parent::__construct(
            [
                'eventSettings'   => $config->getConfig(),
                'eventDetails'    => null,
                'event'           => $this->getEventArray($log->getEvent()),
                'lead'            => $log->getLead(),
                'systemTriggered' => true,
                'dateScheduled'   => $log->getTriggerDate(),
            ],
            $log
        );
    }

    /**
     * @return AbstractEventAccessor
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return ArrayCollection
     */
    public function getLog()
    {
        return $this->log;
    }
}
