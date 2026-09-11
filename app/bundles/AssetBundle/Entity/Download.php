<?php

namespace Mautic\AssetBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\EmailBundle\Entity\Email;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('asset:assets:viewown')"),
        new Get(security: "is_granted('asset:assets:viewown', object)"),
    ],
    normalizationContext: [
        'groups'                  => ['download:read'],
        'swagger_definition_name' => 'Read',
        'api_included'            => ['asset', 'ipaddress', 'email'],
    ],
    denormalizationContext: [
        'groups'                  => ['download:write'],
        'swagger_definition_name' => 'Write',
    ]
)]
#[ORM\Entity(repositoryClass: DownloadRepository::class)]
#[ORM\Table(name: self::TABLE_NAME)]
#[ORM\Index(columns: ['tracking_id'], name: 'download_tracking_search')]
#[ORM\Index(columns: ['source', 'source_id'], name: 'download_source_search')]
#[ORM\Index(columns: ['date_download'], name: 'asset_date_download')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class Download
{
    public const TABLE_NAME = 'asset_downloads';

    /**
     * @var string
     */
    #[Groups(['download:read'])]
    private $id;

    /**
     * @var \DateTimeInterface
     */
    #[Groups(['download:read', 'download:write'])]
    #[ORM\Column(name: 'date_download', type: 'datetime')]
    private $dateDownload;

    /**
     * @var Asset|null
     */
    #[Groups(['download:read', 'download:write'])]
    private $asset;

    /**
     * @var IpAddress|null
     */
    #[Groups(['download:read', 'download:write'])]
    private $ipAddress;

    #[Groups(['download:read', 'download:write'])]
    private ?Lead $lead = null;

    /**
     * @var int
     */
    #[Groups(['download:read', 'download:write'])]
    #[ORM\Column(type: 'integer')]
    private $code;

    /**
     * @var string|null
     */
    #[Groups(['download:read', 'download:write'])]
    #[ORM\Column(type: 'text', nullable: true)]
    private $referer;

    /**
     * @var string
     */
    #[Groups(['download:read', 'download:write'])]
    #[ORM\Column(name: 'tracking_id', type: 'string', length: 191)]
    private $trackingId;

    /**
     * @var string|null
     */
    #[Groups(['download:read', 'download:write'])]
    #[ORM\Column(type: 'string', length: 191, nullable: true)]
    private $source;

    /**
     * @var int|null
     */
    #[Groups(['download:read', 'download:write'])]
    #[ORM\Column(name: 'source_id', type: 'integer', nullable: true)]
    private $sourceId;

    #[Groups(['download:read', 'download:write'])]
    #[ORM\ManyToOne(targetEntity: Email::class)]
    #[ORM\JoinColumn(name: 'email_id', onDelete: 'SET NULL')]
    private ?Email $email = null;

    #[ORM\Column(name: 'utm_campaign', type: Types::STRING, length: 191, nullable: true)]
    private ?string $utmCampaign = null;

    #[ORM\Column(name: 'utm_content', type: Types::STRING, length: 191, nullable: true)]
    private ?string $utmContent = null;

    #[ORM\Column(name: 'utm_medium', type: Types::STRING, length: 191, nullable: true)]
    private ?string $utmMedium = null;

    #[ORM\Column(name: 'utm_source', type: Types::STRING, length: 191, nullable: true)]
    private ?string $utmSource = null;

    #[ORM\Column(name: 'utm_term', type: Types::STRING, length: 191, nullable: true)]
    private ?string $utmTerm = null;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->addBigIntIdField();

        $builder->createManyToOne('asset', 'Asset')
            ->addJoinColumn('asset_id', 'id', true, false, 'CASCADE')
            ->isOwnershipParent()
            ->build();

        $builder->addIpAddress(true);

        $builder->addLead(true, 'SET NULL');
    }

    public function getId(): int
    {
        return (int) $this->id;
    }

    /**
     * @param \DateTime $dateDownload
     */
    public function setDateDownload($dateDownload): static
    {
        $this->dateDownload = $dateDownload;

        return $this;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getDateDownload()
    {
        return $this->dateDownload;
    }

    /**
     * @param int $code
     */
    public function setCode($code): static
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return int
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $referer
     */
    public function setReferer($referer): static
    {
        $this->referer = $referer;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getReferer()
    {
        return $this->referer;
    }

    public function setAsset(?Asset $asset = null): static
    {
        $this->asset = $asset;

        return $this;
    }

    /**
     * @return Asset|null
     */
    public function getAsset()
    {
        return $this->asset;
    }

    public function setIpAddress(IpAddress $ipAddress): static
    {
        $this->ipAddress = $ipAddress;

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
     * @param string $trackingId
     */
    public function setTrackingId($trackingId): static
    {
        $this->trackingId = $trackingId;

        return $this;
    }

    /**
     * @return string
     */
    public function getTrackingId()
    {
        return $this->trackingId;
    }

    public function getLead(): ?Lead
    {
        return $this->lead;
    }

    public function setLead(?Lead $lead): void
    {
        $this->lead = $lead;
    }

    /**
     * @return string|null
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
     * @return int|null
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

    public function getEmail(): ?Email
    {
        return $this->email;
    }

    public function setEmail(?Email $email): void
    {
        $this->email = $email;
    }

    public function getUtmCampaign(): ?string
    {
        return $this->utmCampaign;
    }

    public function setUtmCampaign(?string $utmCampaign): static
    {
        $this->utmCampaign = $utmCampaign;

        return $this;
    }

    public function getUtmContent(): ?string
    {
        return $this->utmContent;
    }

    public function setUtmContent(?string $utmContent): static
    {
        $this->utmContent = $utmContent;

        return $this;
    }

    public function getUtmMedium(): ?string
    {
        return $this->utmMedium;
    }

    public function setUtmMedium(?string $utmMedium): static
    {
        $this->utmMedium = $utmMedium;

        return $this;
    }

    public function getUtmSource(): ?string
    {
        return $this->utmSource;
    }

    public function setUtmSource(?string $utmSource): static
    {
        $this->utmSource = $utmSource;

        return $this;
    }

    public function getUtmTerm(): ?string
    {
        return $this->utmTerm;
    }

    public function setUtmTerm(?string $utmTerm): static
    {
        $this->utmTerm = $utmTerm;

        return $this;
    }

    public function getPermissionUser(): mixed
    {
        return $this->asset->getCreatedBy();
    }
}
