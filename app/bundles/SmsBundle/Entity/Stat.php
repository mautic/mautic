<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;

#[ORM\Entity(repositoryClass: StatRepository::class)]
#[ORM\Table(name: self::TABLE_NAME)]
#[ORM\Index(columns: ['sms_id', 'lead_id'], name: 'stat_sms_search')]
#[ORM\Index(columns: ['tracking_hash'], name: 'stat_sms_hash_search')]
#[ORM\Index(columns: ['source', 'source_id'], name: 'stat_sms_source_search')]
#[ORM\Index(columns: ['is_failed'], name: 'stat_sms_failed_search')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class Stat
{
    public const TABLE_NAME = 'sms_message_stats';

    /**
     * @var string
     */
    #[ORM\Id]
    #[ORM\Column(type: 'bigint', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private $id;

    #[ORM\ManyToOne(targetEntity: Sms::class, inversedBy: 'stats')]
    #[ORM\JoinColumn(name: 'sms_id', onDelete: 'SET NULL')]
    private ?\Mautic\SmsBundle\Entity\Sms $sms = null;

    #[ORM\ManyToOne(targetEntity: \Mautic\LeadBundle\Entity\Lead::class)]
    #[ORM\JoinColumn(name: 'lead_id', onDelete: 'SET NULL')]
    private ?\Mautic\LeadBundle\Entity\Lead $lead = null;

    #[ORM\ManyToOne(targetEntity: LeadList::class)]
    #[ORM\JoinColumn(name: 'list_id', onDelete: 'SET NULL')]
    private ?\Mautic\LeadBundle\Entity\LeadList $list = null;

    #[ORM\ManyToOne(targetEntity: \Mautic\CoreBundle\Entity\IpAddress::class, cascade: ['persist', 'merge', 'detach'])]
    #[ORM\JoinColumn(name: 'ip_id', onDelete: 'SET NULL')]
    private ?\Mautic\CoreBundle\Entity\IpAddress $ipAddress = null;

    /**
     * @var \DateTimeInterface
     */
    #[ORM\Column(name: 'date_sent', type: 'datetime')]
    private $dateSent;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'tracking_hash', type: 'string', length: 191, nullable: true)]
    private $trackingHash;

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 191, nullable: true)]
    private $source;

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'source_id', type: 'integer', nullable: true)]
    private $sourceId;

    #[ORM\Column(type: 'array', nullable: true)]
    private array $tokens = [];

    /**
     * @var array
     */
    #[ORM\Column(type: Types::JSON)]
    private $details = [];

    /**
     * @var bool|null
     */
    #[ORM\Column(name: 'is_failed', type: 'boolean', nullable: true)]
    private $isFailed = false;

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

    public function getSms(): ?\Mautic\SmsBundle\Entity\Sms
    {
        return $this->sms;
    }

    public function setSms(Sms $sms): static
    {
        $this->sms = $sms;

        return $this;
    }

    public function getLead(): ?\Mautic\LeadBundle\Entity\Lead
    {
        return $this->lead;
    }

    public function setLead(Lead $lead): static
    {
        $this->lead = $lead;

        return $this;
    }

    public function getList(): ?\Mautic\LeadBundle\Entity\LeadList
    {
        return $this->list;
    }

    public function setList(LeadList $list): static
    {
        $this->list = $list;

        return $this;
    }

    public function getIpAddress(): ?\Mautic\CoreBundle\Entity\IpAddress
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
    public function getTokens(): array
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
