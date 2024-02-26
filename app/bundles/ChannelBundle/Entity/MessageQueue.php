<?php

namespace Mautic\ChannelBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\LeadBundle\Entity\Lead;

class MessageQueue
{
    public const STATUS_RESCHEDULED = 'rescheduled';

    public const STATUS_PENDING     = 'pending';

    public const STATUS_SENT        = 'sent';

    public const STATUS_CANCELLED   = 'cancelled';

    public const PRIORITY_NORMAL = 2;

    public const PRIORITY_HIGH   = 1;

    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $channel;

    private $channelId;

    /**
     * @var Event|null
     */
    private $event;

    /**
     * @var \Mautic\LeadBundle\Entity\Lead
     */
    private $lead;

    /**
     * @var int
     */
    private $priority = 2;

    /**
     * @var int
     */
    private $maxAttempts = 3;

    /**
     * @var int
     */
    private $attempts = 0;

    /**
     * @var bool
     */
    private $success = false;

    /**
     * @var string
     */
    private $status = self::STATUS_PENDING;

    /**
     * @var \DateTimeInterface
     **/
    private $datePublished;

    /**
     * @var \DateTimeInterface|null
     */
    private $scheduledDate;

    /**
     * @var \DateTimeInterface|null
     */
    private $lastAttempt;

    /**
     * @var \DateTimeInterface|null
     */
    private $dateSent;

    private $options = [];

    /**
     * Used by listeners to note if the message had been processed in bulk.
     *
     * @var bool
     */
    private $processed = false;

    /**
     * Used by listeners to tell the event dispatcher the message needs to be retried in 15 minutes.
     *
     * @var bool
     */
    private $failed = false;

    /**
     * @var bool
     */
    private $metadataUpdated = false;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('message_queue')
            ->setCustomRepositoryClass(\Mautic\ChannelBundle\Entity\MessageQueueRepository::class)
            ->addIndex(['status'], 'message_status_search')
            ->addIndex(['date_sent'], 'message_date_sent')
            ->addIndex(['scheduled_date'], 'message_scheduled_date')
            ->addIndex(['priority'], 'message_priority')
            ->addIndex(['success'], 'message_success')
            ->addIndex(['channel', 'channel_id'], 'message_channel_search');

        $builder->addBigIntIdField();

        $builder->addField('channel', 'string');
        $builder->addNamedField('channelId', 'integer', 'channel_id');

        $builder->createManyToOne('event', \Mautic\CampaignBundle\Entity\Event::class)
            ->addJoinColumn('event_id', 'id', true, false, 'CASCADE')
            ->build();

        $builder->addLead(false, 'CASCADE', false);

        $builder->createField('priority', 'smallint')
            ->columnName('priority')
            ->build();

        $builder->createField('maxAttempts', 'smallint')
            ->columnName('max_attempts')
            ->build();

        $builder->createField('attempts', 'smallint')
            ->columnName('attempts')
            ->build();

        $builder->createField('success', 'boolean')
            ->columnName('success')
            ->build();

        $builder->createField('status', 'string')
            ->columnName('status')
            ->build();

        $builder->createField('datePublished', 'datetime')
            ->columnName('date_published')
            ->nullable()
            ->build();

        $builder->createField('scheduledDate', 'datetime')
            ->columnName('scheduled_date')
            ->nullable()
            ->build();

        $builder->createField('lastAttempt', 'datetime')
            ->columnName('last_attempt')
            ->nullable()
            ->build();

        $builder->createField('dateSent', 'datetime')
            ->columnName('date_sent')
            ->nullable()
            ->build();

        $builder->createField('options', 'array')
            ->nullable()
            ->build();
    }

    public function getId(): int
    {
        return (int) $this->id;
    }

    /**
     * @return int
     */
    public function getAttempts()
    {
        return $this->attempts;
    }

    /**
     * @param int $attempts
     */
    public function setAttempts($attempts): void
    {
        $this->attempts = $attempts;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param array $options
     */
    public function setOptions($options): void
    {
        $this->options[] = $options;
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
     */
    public function setChannel($channel): void
    {
        $this->channel = $channel;
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

    /**
     * @return Event|null
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * @return MessageQueue
     */
    public function setEvent(Event $event)
    {
        $this->event = $event;

        return $this;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getDatePublished()
    {
        return $this->datePublished;
    }

    /**
     * @param \DateTime $datePublished
     */
    public function setDatePublished($datePublished): void
    {
        $this->datePublished = $datePublished;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getDateSent()
    {
        return $this->dateSent;
    }

    /**
     * @param \DateTime $dateSent
     */
    public function setDateSent($dateSent): void
    {
        $this->dateSent = $dateSent;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getLastAttempt()
    {
        return $this->lastAttempt;
    }

    /**
     * @param \DateTime $lastAttempt
     */
    public function setLastAttempt($lastAttempt): void
    {
        $this->lastAttempt = $lastAttempt;
    }

    /**
     * @return Lead
     */
    public function getLead()
    {
        return $this->lead;
    }

    public function setLead(Lead $lead): void
    {
        $this->lead = $lead;
    }

    /**
     * @return int
     */
    public function getMaxAttempts()
    {
        return $this->maxAttempts;
    }

    /**
     * @param int $maxAttempts
     */
    public function setMaxAttempts($maxAttempts): void
    {
        $this->maxAttempts = $maxAttempts;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param int $priority
     */
    public function setPriority($priority): void
    {
        $this->priority = $priority;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getScheduledDate()
    {
        return $this->scheduledDate;
    }

    /**
     * @param mixed $scheduledDate
     */
    public function setScheduledDate($scheduledDate): void
    {
        $this->scheduledDate = $scheduledDate;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus($status): void
    {
        $this->status = $status;
    }

    /**
     * @return bool
     */
    public function getSuccess()
    {
        return $this->success;
    }

    /**
     * @return bool
     */
    public function isSuccess()
    {
        return $this->success;
    }

    /**
     * @param bool $success
     */
    public function setSuccess($success = true): void
    {
        $this->success = $success;
    }

    /**
     * @return bool
     */
    public function isFailed()
    {
        return $this->failed;
    }

    /**
     * @param bool $failed
     *
     * @return MessageQueue
     */
    public function setFailed($failed = true)
    {
        $this->failed = $failed;

        return $this;
    }

    /**
     * @return bool
     */
    public function isProcessed()
    {
        return $this->processed;
    }

    /**
     * @param bool $processed
     *
     * @return MessageQueue
     */
    public function setProcessed($processed = true)
    {
        $this->processed = $processed;

        return $this;
    }

    /**
     * @return array|mixed
     */
    public function getMetadata()
    {
        return $this->options['metadata'] ?? [];
    }

    public function setMetadata(array $metadata = []): void
    {
        $this->metadataUpdated     = true;
        $this->options['metadata'] = $metadata;
    }

    /**
     * @return bool
     */
    public function wasMetadataUpdated()
    {
        return $this->metadataUpdated;
    }
}
