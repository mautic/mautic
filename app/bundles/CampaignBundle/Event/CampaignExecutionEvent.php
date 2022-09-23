<?php

namespace Mautic\CampaignBundle\Event;

use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class CampaignExecutionEvent.
 *
 * @deprecated 2.13.0; to be removed in 3.0
 */
class CampaignExecutionEvent extends Event
{
    use EventArrayTrait;
    use ContextTrait;

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
    protected $eventDetails;

    /**
     * @var bool
     */
    protected $systemTriggered;

    /**
     * @var bool|mixed[]|string|null
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
     * @var string
     */
    protected $channel;

    /**
     * @var int
     */
    protected $channelId;

    /**
     * @param bool|mixed[]|string|null $result
     */
    public function __construct(array $args, $result, LeadEventLog $log = null)
    {
        $this->lead            = $args['lead'];
        $this->event           = $args['event'];
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
        return ($this->event instanceof \Mautic\CampaignBundle\Entity\Event) ? $this->getEventArray($this->event) : $this->event;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->getEvent()['properties'];
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
     * @return bool|mixed[]|string|null
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param bool|mixed[]|string|null $result
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
     * @param string|null $reason
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
     * @param string          $channel
     * @param string|int|null $channelId
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
