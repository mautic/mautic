<?php

namespace Mautic\LeadBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

#[ORM\Entity(repositoryClass: UtmTagRepository::class)]
#[ORM\Table(name: 'lead_utmtags')]
#[ORM\Index(columns: ['date_added'], name: 'utm_date_added')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class UtmTag
{
    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private $id;

    #[ORM\Column(name: 'date_added', type: 'datetime')]
    private ?\DateTimeInterface $dateAdded = null;

    #[ORM\ManyToOne(targetEntity: \Mautic\LeadBundle\Entity\Lead::class, inversedBy: 'utmtags')]
    #[ORM\JoinColumn(name: 'lead_id', nullable: false, onDelete: 'CASCADE')]
    private ?\Mautic\LeadBundle\Entity\Lead $lead = null;

    /**
     * @var array
     */
    #[ORM\Column(type: Types::ARRAY, nullable: true)]
    private $query = [];

    /**
     * @var string|null
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private $referer;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'remote_host', type: Types::STRING, length: 191, nullable: true)]
    private $remoteHost;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private $url;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'user_agent', type: Types::TEXT, nullable: true)]
    private $userAgent;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'utm_campaign', type: Types::STRING, length: 191, nullable: true)]
    private $utmCampaign;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'utm_content', type: Types::STRING, length: 191, nullable: true)]
    private $utmContent;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'utm_medium', type: Types::STRING, length: 191, nullable: true)]
    private $utmMedium;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'utm_source', type: Types::STRING, length: 191, nullable: true)]
    private $utmSource;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'utm_term', type: Types::STRING, length: 191, nullable: true)]
    private $utmTerm;

    /**
     * Prepares the metadata for API usage.
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata->setGroupPrefix('utmtags')
            ->addListProperties(
                [
                    'id',
                    'lead',
                    'query',
                    'referer',
                    'remoteHost',
                    'url',
                    'userAgent',
                    'utmCampaign',
                    'utmContent',
                    'utmMedium',
                    'utmSource',
                    'utmTerm',
                ]
            )
            ->build();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function setDateAdded(\DateTimeInterface $date): static
    {
        $this->dateAdded = $date;

        return $this;
    }

    public function getDateAdded(): ?\DateTimeInterface
    {
        return $this->dateAdded;
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

    /**
     * @return array
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @param array $query
     */
    public function setQuery($query): static
    {
        $this->query = $query;

        return $this;
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

    /**
     * @param string $remoteHost
     */
    public function setRemoteHost($remoteHost): static
    {
        $this->remoteHost = $remoteHost;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getRemoteHost()
    {
        return $this->remoteHost;
    }

    /**
     * @param string $url
     */
    public function setUrl($url): static
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $userAgent
     */
    public function setUserAgent($userAgent): static
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getUserAgent()
    {
        return $this->userAgent;
    }

    /**
     * @return string|null
     */
    public function getUtmCampaign()
    {
        return $this->utmCampaign;
    }

    /**
     * @param string $utmCampaign
     */
    public function setUtmCampaign($utmCampaign): static
    {
        $this->utmCampaign = $utmCampaign;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getUtmContent()
    {
        return $this->utmContent;
    }

    /**
     * @param string $utmContent
     */
    public function setUtmContent($utmContent): static
    {
        $utmContent       = mb_strlen($utmContent) <= ClassMetadataBuilder::MAX_VARCHAR_INDEXED_LENGTH ? $utmContent : mb_substr($utmContent, 0, ClassMetadataBuilder::MAX_VARCHAR_INDEXED_LENGTH);
        $this->utmContent = $utmContent;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getUtmMedium()
    {
        return $this->utmMedium;
    }

    /**
     * @param string $utmMedium
     */
    public function setUtmMedium($utmMedium): static
    {
        $this->utmMedium = $utmMedium;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getUtmSource()
    {
        return $this->utmSource;
    }

    /**
     * @param string $utmSource
     */
    public function setUtmSource($utmSource): static
    {
        $this->utmSource = $utmSource;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getUtmTerm()
    {
        return $this->utmTerm;
    }

    /**
     * @param string $utmTerm
     */
    public function setUtmTerm($utmTerm): static
    {
        $this->utmTerm = $utmTerm;

        return $this;
    }

    public function hasUtmTags(): bool
    {
        return !empty($this->utmCampaign) || !empty($this->utmSource) || !empty($this->utmMedium) || !empty($this->utmContent) || !empty($this->utmTerm);
    }

    /**
     * Available fields and it's setters.
     *
     * @return array<string, string>
     */
    public function getFieldSetterList(): array
    {
        return [
            'utm_campaign' => 'setUtmCampaign',
            'utm_source'   => 'setUtmSource',
            'utm_medium'   => 'setUtmMedium',
            'utm_content'  => 'setUtmContent',
            'utm_term'     => 'setUtmTerm',
            'user_agent'   => 'setUserAgent',
            'url'          => 'setUrl',
            'referer'      => 'setReferer',
            'query'        => 'setQuery',
            'remote_host'  => 'setRemoteHost',
            'date_added'   => 'setDateAdded',
        ];
    }
}
