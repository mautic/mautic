<?php

namespace Mautic\NotificationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\LeadBundle\Entity\Lead;

class Stat
{
    public const TABLE_NAME = 'push_notification_stats';
    /**
     * @var string
     */
    private $id;

    /**
     * @var Notification|null
     */
    private $notification;

    /**
     * @var Lead|null
     */
    private $lead;

    /**
     * @var \Mautic\LeadBundle\Entity\LeadList|null
     */
    private $list;

    /**
     * @var IpAddress|null
     */
    private $ipAddress;

    /**
     * @var \DateTimeInterface
     */
    private $dateSent;

    /**
     * @var \DateTimeInterface
     */
    private $dateRead;

    /**
     * @var bool
     */
    private $isClicked = false;

    /**
     * @var \DateTimeInterface
     */
    private $dateClicked;

    /**
     * @var string|null
     */
    private $trackingHash;

    /**
     * @var int|null
     */
    private $retryCount = 0;

    /**
     * @var string|null
     */
    private $source;

    /**
     * @var int|null
     */
    private $sourceId;

    /**
     * @var array
     */
    private $tokens = [];

    /**
     * @var int|null
     */
    private $clickCount;

    /**
     * @var array
     */
    private $clickDetails = [];

    /**
     * @var \DateTimeInterface
     */
    private $lastClicked;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable(self::TABLE_NAME)
            ->setCustomRepositoryClass(StatRepository::class)
            ->addIndex(['notification_id', 'lead_id'], 'stat_notification_search')
            ->addIndex(['is_clicked'], 'stat_notification_clicked_search')
            ->addIndex(['tracking_hash'], 'stat_notification_hash_search')
            ->addIndex(['source', 'source_id'], 'stat_notification_source_search');

        $builder->addBigIntIdField();

        $builder->createManyToOne('notification', 'Notification')
            ->inversedBy('stats')
            ->addJoinColumn('notification_id', 'id', true, false, 'SET NULL')
            ->build();

        $builder->addLead(true, 'SET NULL');

        $builder->createManyToOne('list', \Mautic\LeadBundle\Entity\LeadList::class)
            ->addJoinColumn('list_id', 'id', true, false, 'SET NULL')
            ->build();

        $builder->addIpAddress(true);

        $builder->createField('dateSent', 'datetime')
            ->columnName('date_sent')
            ->build();

        $builder->createField('dateRead', 'datetime')
            ->columnName('date_read')
            ->nullable()
            ->build();

        $builder->createField('isClicked', 'boolean')
            ->columnName('is_clicked')
            ->build();

        $builder->createField('dateClicked', 'datetime')
            ->columnName('date_clicked')
            ->nullable()
            ->build();

        $builder->createField('trackingHash', 'string')
            ->columnName('tracking_hash')
            ->nullable()
            ->build();

        $builder->createField('retryCount', 'integer')
            ->columnName('retry_count')
            ->nullable()
            ->build();

        $builder->createField('source', 'string')
            ->nullable()
            ->build();

        $builder->createField('sourceId', 'integer')
            ->columnName('source_id')
            ->nullable()
            ->build();

        $builder->createField('tokens', 'array')
            ->nullable()
            ->build();

        $builder->addNullableField('clickCount', 'integer', 'click_count');

        $builder->addNullableField('lastClicked', 'datetime', 'last_clicked');

        $builder->addNullableField('clickDetails', 'array', 'click_details');
    }

    /**
     * Prepares the metadata for API usage.
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata->setGroupPrefix('stat')
            ->addProperties(
                [
                    'id',
                    'ipAddress',
                    'dateSent',
                    'isClicked',
                    'dateClicked',
                    'retryCount',
                    'source',
                    'clickCount',
                    'lastClicked',
                    'sourceId',
                    'trackingHash',
                    'lead',
                    'notification',
                ]
            )
            ->build();
    }

    /**
     * @return mixed
     */
    public function getDateClicked()
    {
        return $this->dateClicked;
    }

    /**
     * @param mixed $dateClicked
     */
    public function setDateClicked($dateClicked): void
    {
        $this->dateClicked = $dateClicked;
    }

    /**
     * @return mixed
     */
    public function getDateSent()
    {
        return $this->dateSent;
    }

    /**
     * @param mixed $dateSent
     */
    public function setDateSent($dateSent): void
    {
        $this->dateSent = $dateSent;
    }

    /**
     * @return Notification
     */
    public function getNotification()
    {
        return $this->notification;
    }

    public function setNotification(Notification $notification = null): void
    {
        $this->notification = $notification;
    }

    public function getId(): int
    {
        return (int) $this->id;
    }

    /**
     * @return IpAddress|null
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    /**
     * @param mixed $ip
     */
    public function setIpAddress(IpAddress $ip): void
    {
        $this->ipAddress = $ip;
    }

    /**
     * @return mixed
     */
    public function getIsClicked()
    {
        return $this->isClicked;
    }

    /**
     * @param mixed $isClicked
     */
    public function setIsClicked($isClicked): void
    {
        $this->isClicked = $isClicked;
    }

    /**
     * @return Lead
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * @param mixed $lead
     */
    public function setLead(Lead $lead = null): void
    {
        $this->lead = $lead;
    }

    /**
     * @return mixed
     */
    public function getTrackingHash()
    {
        return $this->trackingHash;
    }

    /**
     * @param mixed $trackingHash
     */
    public function setTrackingHash($trackingHash): void
    {
        $this->trackingHash = $trackingHash;
    }

    /**
     * @return \Mautic\LeadBundle\Entity\LeadList
     */
    public function getList()
    {
        return $this->list;
    }

    /**
     * @param mixed $list
     */
    public function setList($list): void
    {
        $this->list = $list;
    }

    /**
     * @return mixed
     */
    public function getRetryCount()
    {
        return $this->retryCount;
    }

    /**
     * @param mixed $retryCount
     */
    public function setRetryCount($retryCount): void
    {
        $this->retryCount = $retryCount;
    }

    public function upRetryCount(): void
    {
        ++$this->retryCount;
    }

    /**
     * @return mixed
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param mixed $source
     */
    public function setSource($source): void
    {
        $this->source = $source;
    }

    /**
     * @return mixed
     */
    public function getSourceId()
    {
        return $this->sourceId;
    }

    /**
     * @param mixed $sourceId
     */
    public function setSourceId($sourceId): void
    {
        $this->sourceId = (int) $sourceId;
    }

    /**
     * @return mixed
     */
    public function getTokens()
    {
        return $this->tokens;
    }

    /**
     * @param mixed $tokens
     */
    public function setTokens($tokens): void
    {
        $this->tokens = $tokens;
    }

    /**
     * @return mixed
     */
    public function getClickCount()
    {
        return $this->clickCount;
    }

    /**
     * @param mixed $clickCount
     *
     * @return Stat
     */
    public function setClickCount($clickCount)
    {
        $this->clickCount = $clickCount;

        return $this;
    }

    public function addClickDetails($details): void
    {
        $this->clickDetails[] = $details;

        ++$this->clickCount;
    }

    /**
     * Up the sent count.
     *
     * @return Stat
     */
    public function upClickCount()
    {
        $count            = (int) $this->clickCount + 1;
        $this->clickCount = $count;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLastClicked()
    {
        return $this->lastClicked;
    }

    /**
     * @return Stat
     */
    public function setLastClicked(\DateTime $lastClicked)
    {
        $this->lastClicked = $lastClicked;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getClickDetails()
    {
        return $this->clickDetails;
    }

    /**
     * @param mixed $clickDetails
     *
     * @return Stat
     */
    public function setClickDetails($clickDetails)
    {
        $this->clickDetails = $clickDetails;

        return $this;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getDateRead()
    {
        return $this->dateRead;
    }

    /**
     * @param \DateTime $dateRead
     *
     * @return Stat
     */
    public function setDateRead($dateRead)
    {
        $this->dateRead = $dateRead;

        return $this;
    }
}
