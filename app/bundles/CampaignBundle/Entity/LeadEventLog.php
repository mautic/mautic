<?php

namespace Mautic\CampaignBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\LeadBundle\Entity\Lead as LeadEntity;

class LeadEventLog implements ChannelInterface
{
    /**
     * @var int|null
     */
    private $id;

    /**
     * @var Event|null
     */
    private $event;

    /**
     * @var LeadEntity|null
     */
    private $lead;

    /**
     * @var Campaign|null
     */
    private $campaign;

    /**
     * @var IpAddress|null
     */
    private $ipAddress;

    /**
     * @var \DateTime|null
     **/
    private $dateTriggered;

    /**
     * @var bool
     */
    private $isScheduled = false;

    /**
     * @var \DateTime|null
     */
    private $triggerDate;

    /**
     * @var bool
     */
    private $systemTriggered = false;

    /**
     * @var array
     */
    private $metadata = [];

    /**
     * @var bool
     */
    private $nonActionPathTaken = false;

    /**
     * @var string|null
     */
    private $channel;

    /**
     * @var int|null
     */
    private $channelId;

    /**
     * @var bool|null
     */
    private $previousScheduledState;

    /**
     * @var int
     */
    private $rotation = 1;

    /**
     * @var FailedLeadEventLog|null
     */
    private $failedLog;

    /**
     * Subscribers can fail log with custom reschedule interval.
     *
     * @var \DateInterval|null
     */
    private $rescheduleInterval;

    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('campaign_lead_event_log')
            ->setCustomRepositoryClass('Mautic\CampaignBundle\Entity\LeadEventLogRepository')
            ->addIndex(['is_scheduled', 'lead_id'], 'campaign_event_upcoming_search')
            ->addIndex(['campaign_id', 'is_scheduled', 'trigger_date'], 'campaign_event_schedule_counts')
            ->addIndex(['date_triggered'], 'campaign_date_triggered')
            ->addIndex(['lead_id', 'campaign_id', 'rotation'], 'campaign_leads')
            ->addIndex(['channel', 'channel_id', 'lead_id'], 'campaign_log_channel')
            ->addIndex(['campaign_id', 'event_id', 'date_triggered'], 'campaign_actions')
            ->addIndex(['campaign_id', 'date_triggered', 'event_id', 'non_action_path_taken'], 'campaign_stats')
            ->addIndex(['trigger_date'], 'campaign_trigger_date_order')
            ->addUniqueConstraint(['event_id', 'lead_id', 'rotation'], 'campaign_rotation');

        $builder->addBigIntIdField();

        $builder->createManyToOne('event', 'Event')
            ->inversedBy('log')
            ->addJoinColumn('event_id', 'id', false, false, 'CASCADE')
            ->build();

        $builder->addLead(false, 'CASCADE');

        $builder->addField('rotation', 'integer');

        $builder->createManyToOne('campaign', 'Campaign')
            ->addJoinColumn('campaign_id', 'id')
            ->build();

        $builder->addIpAddress(true);

        $builder->createField('dateTriggered', 'datetime')
            ->columnName('date_triggered')
            ->nullable()
            ->build();

        $builder->createField('isScheduled', 'boolean')
            ->columnName('is_scheduled')
            ->build();

        $builder->createField('triggerDate', 'datetime')
            ->columnName('trigger_date')
            ->nullable()
            ->build();

        $builder->createField('systemTriggered', 'boolean')
            ->columnName('system_triggered')
            ->build();

        $builder->createField('metadata', 'array')
            ->nullable()
            ->build();

        $builder->createField('channel', 'string')
                ->nullable()
                ->build();

        $builder->addNamedField('channelId', 'integer', 'channel_id', true);

        $builder->addNullableField('nonActionPathTaken', 'boolean', 'non_action_path_taken');

        $builder->createOneToOne('failedLog', 'FailedLeadEventLog')
            ->mappedBy('log')
            ->fetchExtraLazy()
            ->cascadeAll()
            ->build();
    }

    /**
     * Prepares the metadata for API usage.
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata)
    {
        $metadata->setGroupPrefix('campaignEventLog')
            ->addProperties(
                [
                    'ipAddress',
                    'dateTriggered',
                    'isScheduled',
                    'triggerDate',
                    'metadata',
                    'nonActionPathTaken',
                    'channel',
                    'channelId',
                    'rotation',
                ]
            )

            // Add standalone groups
            ->setGroupPrefix('campaignEventStandaloneLog')
            ->addProperties(
                [
                    'event',
                    'lead',
                    'campaign',
                    'ipAddress',
                    'dateTriggered',
                    'isScheduled',
                    'triggerDate',
                    'metadata',
                    'nonActionPathTaken',
                    'channel',
                    'channelId',
                    'rotation',
                ]
            )
            ->build();
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \DateTime|null
     */
    public function getDateTriggered()
    {
        return $this->dateTriggered;
    }

    /**
     * @return $this
     */
    public function setDateTriggered(\DateTime $dateTriggered = null)
    {
        $this->dateTriggered = $dateTriggered;
        if (null !== $dateTriggered) {
            $this->setIsScheduled(false);
        }

        return $this;
    }

    /**
     * @return IpAddress|null
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    /**
     * @return $this
     */
    public function setIpAddress(IpAddress $ipAddress)
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    /**
     * @return LeadEntity|null
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * @return $this
     */
    public function setLead(LeadEntity $lead)
    {
        $this->lead = $lead;

        return $this;
    }

    /**
     * @return Event|null
     */
    public function getEvent()
    {
        return $this->event;
    }

    /***
     * @param $event
     *
     * @return $this
     */
    public function setEvent(Event $event)
    {
        $this->event = $event;

        if (!$this->campaign) {
            $this->setCampaign($event->getCampaign());
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function getIsScheduled()
    {
        return $this->isScheduled;
    }

    /**
     * @param bool $isScheduled
     *
     * @return $this
     */
    public function setIsScheduled($isScheduled)
    {
        if (null === $this->previousScheduledState) {
            $this->previousScheduledState = $this->isScheduled;
        }

        $this->isScheduled = $isScheduled;

        return $this;
    }

    /**
     * If isScheduled was changed, this will have the previous state.
     *
     * @return bool|null
     */
    public function getPreviousScheduledState()
    {
        return $this->previousScheduledState;
    }

    /**
     * @return \DateTime|null
     */
    public function getTriggerDate()
    {
        return $this->triggerDate;
    }

    /**
     * @return $this
     */
    public function setTriggerDate(\DateTime $triggerDate = null)
    {
        $this->triggerDate = $triggerDate;
        $this->setIsScheduled(true);

        return $this;
    }

    /**
     * @return Campaign|null
     */
    public function getCampaign()
    {
        return $this->campaign;
    }

    /**
     * @return $this
     */
    public function setCampaign(Campaign $campaign)
    {
        $this->campaign = $campaign;

        return $this;
    }

    /**
     * @return bool
     */
    public function getSystemTriggered()
    {
        return $this->systemTriggered;
    }

    /**
     * @param bool $systemTriggered
     *
     * @return $this
     */
    public function setSystemTriggered($systemTriggered)
    {
        $this->systemTriggered = $systemTriggered;

        return $this;
    }

    /**
     * @return bool
     */
    public function getNonActionPathTaken()
    {
        return $this->nonActionPathTaken;
    }

    /**
     * @param bool $nonActionPathTaken
     *
     * @return $this
     */
    public function setNonActionPathTaken($nonActionPathTaken)
    {
        $this->nonActionPathTaken = $nonActionPathTaken;

        return $this;
    }

    /**
     * @return mixed[]|null
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * @param mixed[] $metadata
     */
    public function appendToMetadata($metadata)
    {
        if (!is_array($metadata)) {
            // Assumed output for timeline BC for <2.14
            $metadata = ['timeline' => $metadata];
        }

        $this->metadata = array_merge($this->metadata, $metadata);
    }

    /**
     * @param mixed[] $metadata
     *
     * @return $this
     */
    public function setMetadata($metadata)
    {
        if (!is_array($metadata)) {
            // Assumed output for timeline
            $metadata = ['timeline' => $metadata];
        }

        $this->metadata = $metadata;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @param string $channel
     *
     * @return LeadEventLog|$this
     */
    public function setChannel($channel)
    {
        $this->channel = $channel;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getChannelId()
    {
        return $this->channelId;
    }

    /**
     * @param int|null $channelId
     *
     * @return LeadEventLog
     */
    public function setChannelId($channelId)
    {
        $this->channelId = $channelId;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getRotation()
    {
        return $this->rotation;
    }

    /**
     * @param int $rotation
     *
     * @return LeadEventLog
     */
    public function setRotation($rotation)
    {
        $this->rotation = (int) $rotation;

        return $this;
    }

    /**
     * @return FailedLeadEventLog|null
     */
    public function getFailedLog()
    {
        return $this->failedLog;
    }

    /**
     * @return $this
     */
    public function setFailedLog(FailedLeadEventLog $log = null)
    {
        $this->failedLog = $log;

        return $this;
    }

    /**
     * @return bool
     */
    public function isFailed()
    {
        $log = $this->getFailedLog();

        return !empty($log);
    }

    /**
     * @return bool
     */
    public function isSuccess()
    {
        return !$this->isFailed();
    }

    public function setRescheduleInterval(?\DateInterval $interval): void
    {
        $this->rescheduleInterval = $interval;
    }

    public function getRescheduleInterval(): ?\DateInterval
    {
        return $this->rescheduleInterval;
    }
}
