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

class ConditionEvent extends Event
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
    private $passed = false;

    /**
     * DecisionEvent constructor.
     *
     * @param AbstractEventAccessor $config
     * @param LeadEventLog          $log
     */
    public function __construct(AbstractEventAccessor $config, LeadEventLog $log)
    {
        $this->eventConfig = $config;
        $this->eventLog    = $log;
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
     * Pass this condition.
     */
    public function pass()
    {
        $this->passed = true;
    }

    /**
     * Fail this condition.
     */
    public function fail()
    {
        $this->passed = false;
    }

    /**
     * @return bool
     */
    public function wasConditionSatisfied()
    {
        return $this->passed;
    }
}
