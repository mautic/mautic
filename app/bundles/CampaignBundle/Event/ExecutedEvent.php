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

class ExecutedEvent extends \Symfony\Component\EventDispatcher\Event
{
    /**
     * @var AbstractEventAccessor
     */
    private $config;

    /**
     * @var LeadEventLog
     */
    private $log;

    /**
     * ExecutedEvent constructor.
     *
     * @param AbstractEventAccessor $config
     * @param LeadEventLog          $log
     */
    public function __construct(AbstractEventAccessor $config, LeadEventLog $log)
    {
        $this->config = $config;
        $this->log    = $log;
    }

    /**
     * @return AbstractEventAccessor
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return LeadEventLog
     */
    public function getLog()
    {
        return $this->log;
    }
}
