<?php

namespace Mautic\PageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\EmailBundle\Entity\Email;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadDevice;
use Mautic\PageBundle\Validator\PageHit;

#[PageHit]
#[ORM\Entity(repositoryClass: HitRepository::class)]
#[ORM\Table(name: self::TABLE_NAME)]
#[ORM\Index(columns: ['tracking_id'], name: 'page_hit_tracking_search')]
#[ORM\Index(columns: ['code'], name: 'page_hit_code_search')]
#[ORM\Index(columns: ['source', 'source_id'], name: 'page_hit_source_search')]
#[ORM\Index(columns: ['date_hit', 'date_left'], name: 'date_hit_left_index')]
#[ORM\Index(columns: ['url'], name: 'page_hit_url', options: ['lengths' => [0 => 128]])]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class Hit
{
    public const TABLE_NAME = 'page_hits';

    /**
     * @var string
     */
    #[ORM\Id]
    #[ORM\Column(type: 'bigint', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private $id;

    /**
     * @var \DateTimeInterface
     */
    #[ORM\Column(name: 'date_hit', type: 'datetime')]
    private $dateHit;

    /**
     * @var \DateTimeInterface
     */
    #[ORM\Column(name: 'date_left', type: 'datetime', nullable: true)]
    private $dateLeft;

    #[ORM\ManyToOne(targetEntity: 'Page')]
    #[ORM\JoinColumn(name: 'page_id', onDelete: 'SET NULL')]
    private ?Page $page = null;

    /**
     * @var Redirect|null
     */
    #[ORM\ManyToOne(targetEntity: 'Redirect')]
    #[ORM\JoinColumn(name: 'redirect_id', onDelete: 'SET NULL')]
    private $redirect;

    #[ORM\ManyToOne(targetEntity: Email::class)]
    #[ORM\JoinColumn(name: 'email_id', onDelete: 'SET NULL')]
    private ?Email $email = null;

    /**
     * @var Lead|null
     */
    #[ORM\ManyToOne(targetEntity: \Mautic\LeadBundle\Entity\Lead::class)]
    #[ORM\JoinColumn(name: 'lead_id', onDelete: 'SET NULL')]
    private $lead;

    /**
     * @var IpAddress|null
     */
    #[ORM\ManyToOne(targetEntity: \Mautic\CoreBundle\Entity\IpAddress::class, cascade: ['persist', 'merge', 'detach'])]
    #[ORM\JoinColumn(name: 'ip_id', onDelete: 'SET NULL')]
    private $ipAddress;

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 191, nullable: true)]
    private $country;

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 191, nullable: true)]
    private $region;

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 191, nullable: true)]
    private $city;

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 191, nullable: true)]
    private $isp;

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 191, nullable: true)]
    private $organization;

    /**
     * @var int
     */
    #[ORM\Column(type: 'integer')]
    private $code;

    #[ORM\Column(type: 'text', nullable: true)]
    private $referer;

    #[ORM\Column(type: 'text', nullable: true)]
    private $url;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'url_title', type: 'string', length: 191, nullable: true)]
    private $urlTitle;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'user_agent', type: 'text', nullable: true)]
    private $userAgent;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'remote_host', type: 'string', length: 191, nullable: true)]
    private $remoteHost;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'page_language', type: 'string', length: 191, nullable: true)]
    private $pageLanguage;

    /**
     * @var array<string>
     */
    #[ORM\Column(name: 'browser_languages', type: 'array', nullable: true)]
    private $browserLanguages = [];

    /**
     * @var string
     */
    #[ORM\Column(name: 'tracking_id', type: 'string', length: 191)]
    private $trackingId;

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

    /**
     * @var array
     */
    #[ORM\Column(type: 'array', nullable: true)]
    private $query = [];

    /**
     * @var LeadDevice|null
     */
    #[ORM\ManyToOne(targetEntity: LeadDevice::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'device_id', onDelete: 'SET NULL')]
    private $device;

    /**
     * Prepares the metadata for API usage.
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata->setGroupPrefix('hit')
            ->addProperties(
                [
                    'id',
                    'dateHit',
                    'dateLeft',
                    'page',
                    'redirect',
                    'email',
                    'lead',
                    'ipAddress',
                    'country',
                    'region',
                    'city',
                    'isp',
                    'organization',
                    'code',
                    'referer',
                    'url',
                    'urlTitle',
                    'userAgent',
                    'remoteHost',
                    'pageLanguage',
                    'browserLanguages',
                    'trackingId',
                    'source',
                    'sourceId',
                    'query',
                ]
            )
            ->build();
    }

    public function getId(): int
    {
        return (int) $this->id;
    }

    /**
     * @param \DateTime $dateHit
     */
    public function setDateHit($dateHit): static
    {
        $this->dateHit = $dateHit;

        return $this;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getDateHit()
    {
        return $this->dateHit;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getDateLeft()
    {
        return $this->dateLeft;
    }

    /**
     * @param \DateTime $dateLeft
     */
    public function setDateLeft($dateLeft): static
    {
        $this->dateLeft = $dateLeft;

        return $this;
    }

    /**
     * @param string $country
     */
    public function setCountry($country): static
    {
        $this->country = $country;

        return $this;
    }

    /**
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param string $region
     */
    public function setRegion($region): static
    {
        $this->region = $region;

        return $this;
    }

    /**
     * @return string
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * @param string $city
     */
    public function setCity($city): static
    {
        $this->city = $city;

        return $this;
    }

    /**
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param string $isp
     */
    public function setIsp($isp): static
    {
        $this->isp = $isp;

        return $this;
    }

    /**
     * @return string
     */
    public function getIsp()
    {
        return $this->isp;
    }

    /**
     * @param string $organization
     */
    public function setOrganization($organization): static
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * @return string
     */
    public function getOrganization()
    {
        return $this->organization;
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
     * @return string
     */
    public function getReferer()
    {
        return $this->referer;
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
     * @param string $urlTitle
     */
    public function setUrlTitle($urlTitle): static
    {
        $urlTitle       = mb_strlen($urlTitle) <= 191 ? $urlTitle : mb_substr($urlTitle, 0, 191);
        $this->urlTitle = $urlTitle;

        return $this;
    }

    /**
     * @return string
     */
    public function getUrlTitle()
    {
        return $this->urlTitle;
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
     * @return string
     */
    public function getUserAgent()
    {
        return $this->userAgent;
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
     * @return string
     */
    public function getRemoteHost()
    {
        return $this->remoteHost;
    }

    public function setPage(?Page $page = null): static
    {
        $this->page = $page;

        return $this;
    }

    public function getPage(): ?Page
    {
        return $this->page;
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
     * @return string|null
     */
    public function getTrackingId()
    {
        return $this->trackingId;
    }

    /**
     * @param string $pageLanguage
     */
    public function setPageLanguage($pageLanguage): static
    {
        $this->pageLanguage = $pageLanguage;

        return $this;
    }

    /**
     * @return string
     */
    public function getPageLanguage()
    {
        return $this->pageLanguage;
    }

    /**
     * @param array<string> $browserLanguages
     */
    public function setBrowserLanguages($browserLanguages): static
    {
        $this->browserLanguages = $browserLanguages;

        return $this;
    }

    /**
     * @return array<string>
     */
    public function getBrowserLanguages()
    {
        return $this->browserLanguages;
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
     * @return string
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
     * @return int
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
        $this->sourceId = (int) $sourceId;

        return $this;
    }

    /**
     * @return ?Redirect
     */
    public function getRedirect()
    {
        return $this->redirect;
    }

    public function setRedirect(Redirect $redirect): static
    {
        $this->redirect = $redirect;

        return $this;
    }

    public function getEmail(): ?Email
    {
        return $this->email;
    }

    public function setEmail(?Email $email): void
    {
        $this->email = $email;
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
     * @return LeadDevice
     */
    public function getDeviceStat()
    {
        return $this->device;
    }

    public function setDeviceStat(LeadDevice $device): static
    {
        $this->device = $device;

        return $this;
    }
}
