<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

/**
 * Class MessageQueue
 *
 * @package Mautic\CampaignBundle\Entity
 */
class MessageQueue
{

    /**
     * @var string
     */
    private $channel;

    /**
     * @var
     */
    private $channelId;

    /**
     * @var Campaign
     */
    private $campaign;

    /**
     * @var \Mautic\LeadBundle\Entity\Lead
     */
    private $lead;

    /**
     * @var int
     */
    private $priority;

    /**
     * @var int
     */
    private $maxAttempts;

    /**
     * @var int
     */
    private $attempts;

    /**
     * @var bool
     */
    private $success = false;

    /**
     * @var string
     */
    private $status = 'pending';

    /**
     * @var \DateTime
     **/
    private $datePublished;

    /**
     * @var null|\DateTime
     */
    private $scheduledDate;

    /**
     * @var null|\DateTime
     */
    private $lastAttempt;

    /**
     * @var null|\DateTime
     */
    private $dateSent;

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata (ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('campaign_message_queue')
            ->setCustomRepositoryClass('Mautic\CampaignBundle\Entity\MessageQueueRepository')
            ->addIndex(['is_scheduled'], 'event_upcoming_search')
            ->addIndex(['date_triggered'], 'campaign_date_triggered')
            ->addIndex(['lead_id', 'campaign_id'], 'campaign_leads');

        $builder->createManyToOne('event', 'Event')
            ->isPrimaryKey()
            ->inversedBy('log')
            ->addJoinColumn('event_id', 'id', false, false, 'CASCADE')
            ->build();

        $builder->addLead(false, 'CASCADE', true);

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


        $builder->addNullableField('channel', 'string');
        $builder->addNamedField('channelId', 'integer', 'channel_id', true);

        $builder->addNullableField('nonActionPathTaken', 'boolean', 'non_action_path_taken');
    }

    /**
     * @return \DateTime
     */
    public function getDateTriggered ()
    {
        return $this->dateTriggered;
    }

    /**
     * @param \DateTime $dateTriggered
     */
    public function setDateTriggered ($dateTriggered)
    {
        $this->dateTriggered = $dateTriggered;
    }

    /**
     * @return \Mautic\CoreBundle\Entity\IpAddress
     */
    public function getIpAddress ()
    {
        return $this->ipAddress;
    }

    /**
     * @param \Mautic\CoreBundle\Entity\IpAddress $ipAddress
     */
    public function setIpAddress ($ipAddress)
    {
        $this->ipAddress = $ipAddress;
    }

    /**
     * @return mixed
     */
    public function getLead ()
    {
        return $this->lead;
    }

    /**
     * @param mixed $lead
     */
    public function setLead ($lead)
    {
        $this->lead = $lead;
    }

    /**
     * @return mixed
     */
    public function getEvent ()
    {
        return $this->event;
    }

    /**
     * @param mixed $event
     */
    public function setEvent ($event)
    {
        $this->event = $event;
    }

    /**
     * @return bool
     */
    public function getIsScheduled ()
    {
        return $this->isScheduled;
    }

    /**
     * @param bool $isScheduled
     */
    public function setIsScheduled ($isScheduled)
    {
        $this->isScheduled = $isScheduled;
    }

    /**
     * @return mixed
     */
    public function getTriggerDate ()
    {
        return $this->triggerDate;
    }

    /**
     * @param mixed $triggerDate
     */
    public function setTriggerDate ($triggerDate)
    {
        $this->triggerDate = $triggerDate;
    }

    /**
     * @return mixed
     */
    public function getCampaign ()
    {
        return $this->campaign;
    }

    /**
     * @param mixed $campaign
     */
    public function setCampaign ($campaign)
    {
        $this->campaign = $campaign;
    }

    /**
     * @return bool
     */
    public function getSystemTriggered ()
    {
        return $this->systemTriggered;
    }

    /**
     * @param bool $systemTriggered
     */
    public function setSystemTriggered ($systemTriggered)
    {
        $this->systemTriggered = $systemTriggered;
    }

    /**
     * @return mixed
     */
    public function getNonActionPathTaken()
    {
        return $this->nonActionPathTaken;
    }

    /**
     * @param mixed $nonActionPathTaken
     */
    public function setNonActionPathTaken($nonActionPathTaken)
    {
        $this->nonActionPathTaken = $nonActionPathTaken;
    }

    /**
     * @return mixed
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * @param mixed $metatdata
     */
    public function setMetadata($metadata)
    {
        if (!is_array($metadata)) {
            // Assumed output for timeline
            $metadata = array('timeline' => $metadata);
        }

        $this->metadata = $metadata;
    }

    /**
     * @return string
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @param string $channel
     *
     * @return MessageQueue
     */
    public function setChannel($channel)
    {
        $this->channel = $channel;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getChannelId()
    {
        return $this->channelId;
    }

    /**
     * @param mixed $channelId
     *
     * @return MessageQueue
     */
    public function setChannelId($channelId)
    {
        $this->channelId = $channelId;

        return $this;
    }
}
