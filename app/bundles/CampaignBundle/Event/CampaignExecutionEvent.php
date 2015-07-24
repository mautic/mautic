<?php
/**
 * @package     Mautic
 * @copyright   2015 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class CampaignEvent
 *
 * @package Mautic\CampaignBundle\Event
 */
class CampaignExecutionEvent extends Event
{
    protected $lead;
    protected $event;
    protected $config;
    protected $eventDetails;
    protected $systemTriggered;
    protected $result;

    public function __construct($args, $result)
    {
        $this->lead            = $args['lead'];
        $this->event           = $args['event'];
        $this->config          = $args['config'];
        $this->eventDetails    = $args['eventDetails'];
        $this->systemTriggered = $args['systemTriggered'];
        $this->result          = $result;
    }

    /**
     * @return mixed
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * @return mixed
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * @return mixed
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return mixed
     */
    public function getEventDetails()
    {
        return $this->eventDetails;
    }

    /**
     * @return mixed
     */
    public function getSystemTriggered()
    {
        return $this->systemTriggered;
    }

    /**
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }
}