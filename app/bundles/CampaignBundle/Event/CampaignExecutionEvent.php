<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Event;

use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class CampaignExecutionEvent.
 */
class CampaignExecutionEvent extends Event
{
    /**
     * @var Lead
     */
    protected $lead;

    /**
     * @var array
     */
    protected $event;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var array
     */
    protected $eventDetails;

    /**
     * @var bool
     */
    protected $systemTriggered;

    /**
     * @var bool|array
     */
    protected $result;

    /**
     * @var array
     */
    protected $eventSettings;

    /**
     * @var LeadEventLog|null
     */
    protected $log;

    /**
     * @var bool
     */
    protected $logUpdatedByListener = false;

    /**
     * @var
     */
    protected $channel;

    /**
     * @var
     */
    protected $channelId;

    /**
     * CampaignExecutionEvent constructor.
     *
     * @param array             $args
     * @param bool              $result
     * @param LeadEventLog|null $log
     */
    public function __construct(array $args, $result, LeadEventLog $log = null)
    {
        $this->lead            = $args['lead'];
        $this->event           = $args['event'];
        $this->config          = $args['event']['properties'];
        $this->eventDetails    = $args['eventDetails'];
        $this->systemTriggered = $args['systemTriggered'];
        $this->eventSettings   = $args['eventSettings'];
        $this->result          = $result;
        $this->log             = $log;
    }

    /**
     * @return Lead
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * Returns array with lead fields and owner ID if exist.
     *
     * @return array
     */
    public function getLeadFields()
    {
        $lead         = $this->getLead();
        $isLeadEntity = ($lead instanceof Lead);

        // In case Lead is a scalar value:
        if (!$isLeadEntity && !is_array($lead)) {
            $lead = [];
        }

        $leadFields             = $isLeadEntity ? $lead->getProfileFields() : $lead;
        $leadFields['owner_id'] = $isLeadEntity && ($owner = $lead->getOwner()) ? $owner->getId() : 0;

        return $leadFields;
    }

    /**
     * @return array
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return array
     */
    public function getEventDetails()
    {
        return $this->eventDetails;
    }

    /**
     * @return bool
     */
    public function getSystemTriggered()
    {
        return $this->systemTriggered;
    }

    /**
     * @return bool
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param $result
     *
     * @return $this
     */
    public function setResult($result)
    {
        $this->result = $result;

        return $this;
    }

    /**
     * Set the result to failed.
     *
     * @param null $reason
     *
     * @return $this
     */
    public function setFailed($reason = null)
    {
        $this->result = [
            'failed' => 1,
            'reason' => $reason,
        ];

        return $this;
    }

    /**
     * @return mixed
     */
    public function getEventSettings()
    {
        return $this->eventSettings;
    }

    /**
     * Set a custom log entry to override auto-handling of the log entry.
     *
     * @param LeadEventLog $log
     *
     * @return $this
     */
    public function setLogEntry(LeadEventLog $log)
    {
        $this->logUpdatedByListener = true;
        $this->log                  = $log;

        return $this;
    }

    /**
     * @return LeadEventLog
     */
    public function getLogEntry()
    {
        return $this->log;
    }

    /**
     * Returns if a listener updated the log entry.
     *
     * @return bool
     */
    public function wasLogUpdatedByListener()
    {
        return $this->logUpdatedByListener;
    }

    /**
     * Check if an event is applicable.
     *
     * @param $eventType
     */
    public function checkContext($eventType)
    {
        return strtolower($eventType) == strtolower($this->event['type']);
    }

    /**
     * @param      $channel
     * @param null $channelId
     *
     * @return $this
     */
    public function setChannel($channel, $channelId = null)
    {
        if (null !== $this->log) {
            // Set the channel since we have the resource
            $this->log->setChannel($channel)
                      ->setChannelId($channelId);
        }

        $this->channel   = $channel;
        $this->channelId = $channelId;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @return mixed
     */
    public function getChannelId()
    {
        return $this->channelId;
    }
}
