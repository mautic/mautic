<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\LeadBundle\Entity\Lead;

class VideoHit
{
    public const TABLE_NAME = 'video_hits';

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $guid;

    /**
     * @var \DateTimeInterface
     */
    private $dateHit;

    /**
     * @var \DateTimeInterface
     */
    private $dateLeft;

    /**
     * @var int|null
     */
    private $timeWatched;

    /**
     * @var int|null
     */
    private $duration;

    /**
     * @var Redirect
     */
    private $redirect;

    /**
     * @var Lead|null
     */
    private $lead;

    /**
     * @var IpAddress|null
     */
    private $ipAddress;

    /**
     * @var string|null
     */
    private $country;

    /**
     * @var string|null
     */
    private $region;

    /**
     * @var string|null
     */
    private $city;

    /**
     * @var string|null
     */
    private $isp;

    /**
     * @var string|null
     */
    private $organization;

    /**
     * @var int
     */
    private $code;

    private $referer;

    private $url;

    /**
     * @var string|null
     */
    private $userAgent;

    /**
     * @var string|null
     */
    private $remoteHost;

    /**
     * @var string|null
     */
    private $pageLanguage;

    /**
     * @var array<string>
     */
    private $browserLanguages = [];

    /**
     * @var string|null
     */
    private $channel;

    /**
     * @var int|null
     */
    private $channelId;

    /**
     * @var array
     */
    private $query = [];

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable(self::TABLE_NAME)
            ->setCustomRepositoryClass(VideoHitRepository::class)
            ->addIndex(['date_hit'], 'video_date_hit')
            ->addIndex(['channel', 'channel_id'], 'video_channel_search')
            ->addIndex(['guid', 'lead_id'], 'video_guid_lead_search');

        $builder->addId();

        $builder->createField('dateHit', 'datetime')
            ->columnName('date_hit')
            ->build();

        $builder->createField('dateLeft', 'datetime')
            ->columnName('date_left')
            ->nullable()
            ->build();

        $builder->addLead(true, 'SET NULL');

        $builder->addIpAddress(true);

        $builder->createField('country', 'string')
            ->nullable()
            ->build();

        $builder->createField('region', 'string')
            ->nullable()
            ->build();

        $builder->createField('city', 'string')
            ->nullable()
            ->build();

        $builder->createField('isp', 'string')
            ->nullable()
            ->build();

        $builder->createField('organization', 'string')
            ->nullable()
            ->build();

        $builder->addField('code', 'integer');

        $builder->createField('referer', 'text')
            ->nullable()
            ->build();

        $builder->createField('url', 'text')
            ->nullable()
            ->build();

        $builder->createField('userAgent', 'text')
            ->columnName('user_agent')
            ->nullable()
            ->build();

        $builder->createField('remoteHost', 'string')
            ->columnName('remote_host')
            ->nullable()
            ->build();

        $builder->createField('guid', 'string')
            ->columnName('guid')
            ->build();

        $builder->createField('pageLanguage', 'string')
            ->columnName('page_language')
            ->nullable()
            ->build();

        $builder->createField('browserLanguages', 'array')
            ->columnName('browser_languages')
            ->nullable()
            ->build();

        $builder->createField('channel', 'string')
            ->nullable()
            ->build();

        $builder->createField('channelId', 'integer')
            ->columnName('channel_id')
            ->nullable()
            ->build();

        $builder->createField('timeWatched', 'integer')
            ->columnName('time_watched')
            ->nullable()
            ->build();

        $builder->createField('duration', 'integer')
            ->columnName('duration')
            ->nullable()
            ->build();

        $builder->addNullableField('query', 'array');
    }

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

    /**
     * @return IpAddress|null
     */
    public function getIpAddress()
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
    public function getChannelId()
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
    public function getRedirect()
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
