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
     * @return Sms|null
     */
    public function getSms()
    {
        return $this->sms;
    }

    public function setSms(Sms $sms): static
    {
        $this->sms = $sms;

        return $this;
    }

    /**
     * @return Lead|null
     */
    public function getLead()
    {
        return $this->lead;
    }

    public function setLead(Lead $lead): static
    {
        $this->lead = $lead;

        return $this;
    }

    /**
     * @return LeadList|null
     */
    public function getList()
    {
        return $this->list;
    }

    public function setList(LeadList $list): static
    {
        $this->list = $list;

        return $this;
    }

    /**
     * @return IpAddress|null
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    public function setIpAddress(IpAddress $ipAddress): static
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getDateSent()
    {
        return $this->dateSent;
    }

    /**
     * @param \DateTime $dateSent
     */
    public function setDateSent($dateSent): static
    {
        $this->dateSent = $dateSent;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getTrackingHash()
    {
        return $this->trackingHash;
    }

    /**
     * @param string $trackingHash
     */
    public function setTrackingHash($trackingHash): static
    {
        $this->trackingHash = $trackingHash;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param string $source
     */
    public function setSource($source): static
    {
        $this->source = $source;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getSourceId()
    {
        return $this->sourceId;
    }

    /**
     * @param int $sourceId
     */
    public function setSourceId($sourceId): static
    {
        $this->sourceId = $sourceId;

        return $this;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function getTokens()
    {
        return $this->tokens;
    }

    public function setTokens(array $tokens): static
    {
        $this->tokens = $tokens;

        return $this;
    }

    /**
     * @param bool $isFailed
     */
    public function setIsFailed($isFailed): static
    {
        $this->isFailed = $isFailed;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function isFailed()
    {
        return $this->isFailed;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function getDetails()
    {
        return $this->details;
    }

    /**
     * @param array $details
     */
    public function setDetails($details): static
    {
        $this->details = $details;

        return $this;
    }

    /**
     * @param string $type
     * @param string $detail
     */
    public function addDetail($type, $detail): static
    {
        $this->details[$type][] = $detail;

        return $this;
    }
}
