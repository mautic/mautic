<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\LeadBundle\Entity\Lead;

#[ORM\Entity(repositoryClass: VideoHitRepository::class)]
#[ORM\Table(name: self::TABLE_NAME)]
#[ORM\Index(columns: ['date_hit'], name: 'video_date_hit')]
#[ORM\Index(columns: ['channel', 'channel_id'], name: 'video_channel_search')]
#[ORM\Index(columns: ['guid', 'lead_id'], name: 'video_guid_lead_search')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class VideoHit
{
    public const TABLE_NAME = 'video_hits';

    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private $id;

    /**
     * @var string
     */
    #[ORM\Column(type: 'string', length: 191)]
    private $guid;

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

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'time_watched', type: 'integer', nullable: true)]
    private $timeWatched;

    /**
     * @var int|null
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    private $duration;

    private ?\Mautic\PageBundle\Entity\Redirect $redirect = null;

    #[ORM\ManyToOne(targetEntity: \Mautic\LeadBundle\Entity\Lead::class)]
    #[ORM\JoinColumn(name: 'lead_id', onDelete: 'SET NULL')]
    private ?\Mautic\LeadBundle\Entity\Lead $lead = null;

    #[ORM\ManyToOne(targetEntity: \Mautic\CoreBundle\Entity\IpAddress::class, cascade: ['persist', 'merge', 'detach'])]
    #[ORM\JoinColumn(name: 'ip_id', onDelete: 'SET NULL')]
    private ?\Mautic\CoreBundle\Entity\IpAddress $ipAddress = null;

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
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 191, nullable: true)]
    private $channel;

    #[ORM\Column(name: 'channel_id', type: 'integer', nullable: true)]
    private ?int $channelId = null;

    /**
     * @var array
     */
    #[ORM\Column(type: 'array', nullable: true)]
    private $query = [];

    /**
     * Prepares the metadata for API usage.
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata->setGroupPrefix('hit')
            ->addProperties(
                [
                    'dateHit',
                    'dateLeft',
                    'lead',
                    'ipAddress',
                    'country',
                    'region',
                    'city',
                    'isp',
                    'code',
                    'referer',
                    'url',
                    'urlTitle',
                    'userAgent',
                    'remoteHost',
                    'pageLanguage',
                    'browserLanguages',
                    'source',
                    'sourceId',
                    'query',
                    'timeWatched',
                    'guid',
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

    public function setIpAddress(IpAddress $ipAddress): static
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    public function getIpAddress(): ?\Mautic\CoreBundle\Entity\IpAddress
    {
        return $this->ipAddress;
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
     * @return string
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @param string $channel
     */
    public function setChannel($channel): static
    {
        $this->channel = $channel;

        return $this;
    }

    /**
     * @return int
     */
    public function getChannelId(): ?int
    {
        return $this->channelId;
    }

    /**
     * @param int $channelId
     */
    public function setChannelId($channelId): static
    {
        $this->channelId = (int) $channelId;

        return $this;
    }

    /**
     * @return Redirect
     */
    public function getRedirect(): ?\Mautic\PageBundle\Entity\Redirect
    {
        return $this->redirect;
    }

    public function setRedirect(Redirect $redirect): static
    {
        $this->redirect = $redirect;

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
     * @return int
     */
    public function getTimeWatched()
    {
        return $this->timeWatched;
    }

    public function setTimeWatched($timeWatched): static
    {
        $this->timeWatched = $timeWatched;

        return $this;
    }

    /**
     * @return string
     */
    public function getGuid()
    {
        return $this->guid;
    }

    /**
     * @param string $guid
     */
    public function setGuid($guid): static
    {
        $this->guid = $guid;

        return $this;
    }

    /**
     * @return int
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * @param int $duration
     */
    public function setDuration($duration): static
    {
        $this->duration = $duration;

        return $this;
    }
}
