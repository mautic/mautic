<?php

namespace Mautic\SmsBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;

class Stat
{
    public const TABLE_NAME = 'sms_message_stats';

    /**
     * @var string
     */
    private $id;

    /**
     * @var Sms|null
     */
    private $sms;

    /**
     * @var Lead|null
     */
    private $lead;

    /**
     * @var LeadList|null
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
     * @var string|null
     */
    private $trackingHash;

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
     * @var array
     */
    private $details = [];

    /**
     * @var bool|null
     */
    private $isFailed = false;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable(self::TABLE_NAME)
            ->setCustomRepositoryClass(StatRepository::class)
            ->addIndex(['sms_id', 'lead_id'], 'stat_sms_search')
            ->addIndex(['tracking_hash'], 'stat_sms_hash_search')
            ->addIndex(['source', 'source_id'], 'stat_sms_source_search')
            ->addIndex(['is_failed'], 'stat_sms_failed_search');

        $builder->addBigIntIdField();

        $builder->createManyToOne('sms', 'Sms')
            ->inversedBy('stats')
            ->addJoinColumn('sms_id', 'id', true, false, 'SET NULL')
            ->build();

        $builder->addLead(true, 'SET NULL');

        $builder->createManyToOne('list', LeadList::class)
            ->addJoinColumn('list_id', 'id', true, false, 'SET NULL')
            ->build();

        $builder->addIpAddress(true);

        $builder->createField('dateSent', 'datetime')
            ->columnName('date_sent')
            ->build();

        $builder->createField('isFailed', 'boolean')
            ->columnName('is_failed')
            ->nullable()
            ->build();

        $builder->createField('trackingHash', 'string')
            ->columnName('tracking_hash')
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

        $builder->addField('details', Types::JSON);
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
                    'isFailed',
                    'source',
                    'sourceId',
                    'trackingHash',
                    'lead',
                    'sms',
                    'details',
                ]
            )
            ->build();
    }

    public function getId(): int
    {
        return (int) $this->id;
    }

    /**
     * @return Sms
     */
    public function getSms()
    {
        return $this->sms;
    }

    /**
     * @return Stat
     */
    public function setSms(Sms $sms)
    {
        $this->sms = $sms;

        return $this;
    }

    /**
     * @return Lead
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * @return Stat
     */
    public function setLead(Lead $lead)
    {
        $this->lead = $lead;

        return $this;
    }

    /**
     * @return LeadList
     */
    public function getList()
    {
        return $this->list;
    }

    /**
     * @return Stat
     */
    public function setList(LeadList $list)
    {
        $this->list = $list;

        return $this;
    }

    /**
     * @return IpAddress
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    /**
     * @return Stat
     */
    public function setIpAddress(IpAddress $ipAddress)
    {
        $this->ipAddress = $ipAddress;

        return $this;
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
     *
     * @return Stat
     */
    public function setDateSent($dateSent)
    {
        $this->dateSent = $dateSent;

        return $this;
    }

    /**
     * @return string
     */
    public function getTrackingHash()
    {
        return $this->trackingHash;
    }

    /**
     * @param string $trackingHash
     *
     * @return Stat
     */
    public function setTrackingHash($trackingHash)
    {
        $this->trackingHash = $trackingHash;

        return $this;
    }

    /**
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param string $source
     *
     * @return Stat
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * @return int
     */
    public function getSourceId()
    {
        return $this->sourceId;
    }

    /**
     * @param int $sourceId
     *
     * @return Stat
     */
    public function setSourceId($sourceId)
    {
        $this->sourceId = $sourceId;

        return $this;
    }

    /**
     * @return array
     */
    public function getTokens()
    {
        return $this->tokens;
    }

    /**
     * @return Stat
     */
    public function setTokens(array $tokens)
    {
        $this->tokens = $tokens;

        return $this;
    }

    /**
     * @param bool $isFailed
     *
     * @return Stat
     */
    public function setIsFailed($isFailed)
    {
        $this->isFailed = $isFailed;

        return $this;
    }

    /**
     * @return bool
     */
    public function isFailed()
    {
        return $this->isFailed;
    }

    /**
     * @return array
     */
    public function getDetails()
    {
        return $this->details;
    }

    /**
     * @param array $details
     *
     * @return Stat
     */
    public function setDetails($details)
    {
        $this->details = $details;

        return $this;
    }

    /**
     * @param string $type
     * @param string $detail
     *
     * @return Stat
     */
    public function addDetail($type, $detail)
    {
        $this->details[$type][] = $detail;

        return $this;
    }
}
