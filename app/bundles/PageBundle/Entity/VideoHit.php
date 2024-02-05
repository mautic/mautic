<?php

namespace Mautic\PageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\LeadBundle\Entity\Lead;

class VideoHit
{
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
     * @var \Mautic\LeadBundle\Entity\Lead|null
     */
    private $lead;

    /**
     * @var \Mautic\CoreBundle\Entity\IpAddress
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

        $builder->setTable('video_hits')
            ->setCustomRepositoryClass(\Mautic\PageBundle\Entity\VideoHitRepository::class)
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

        $builder->addIpAddress();

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
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set dateHit.
     *
     * @param \DateTime $dateHit
     *
     * @return VideoHit
     */
    public function setDateHit($dateHit)
    {
        $this->dateHit = $dateHit;

        return $this;
    }

    /**
     * Get dateHit.
     *
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
     *
     * @return VideoHit
     */
    public function setDateLeft($dateLeft)
    {
        $this->dateLeft = $dateLeft;

        return $this;
    }

    /**
     * Set country.
     *
     * @param string $country
     *
     * @return VideoHit
     */
    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get country.
     *
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Set region.
     *
     * @param string $region
     *
     * @return VideoHit
     */
    public function setRegion($region)
    {
        $this->region = $region;

        return $this;
    }

    /**
     * Get region.
     *
     * @return string
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * Set city.
     *
     * @param string $city
     *
     * @return VideoHit
     */
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Get city.
     *
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set isp.
     *
     * @param string $isp
     *
     * @return VideoHit
     */
    public function setIsp($isp)
    {
        $this->isp = $isp;

        return $this;
    }

    /**
     * Get isp.
     *
     * @return string
     */
    public function getIsp()
    {
        return $this->isp;
    }

    /**
     * Set organization.
     *
     * @param string $organization
     *
     * @return VideoHit
     */
    public function setOrganization($organization)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * Get organization.
     *
     * @return string
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * Set code.
     *
     * @param int $code
     *
     * @return VideoHit
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code.
     *
     * @return int
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set referer.
     *
     * @param string $referer
     *
     * @return VideoHit
     */
    public function setReferer($referer)
    {
        $this->referer = $referer;

        return $this;
    }

    /**
     * Get referer.
     *
     * @return string
     */
    public function getReferer()
    {
        return $this->referer;
    }

    /**
     * Set url.
     *
     * @param string $url
     *
     * @return VideoHit
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set userAgent.
     *
     * @param string $userAgent
     *
     * @return VideoHit
     */
    public function setUserAgent($userAgent)
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    /**
     * Get userAgent.
     *
     * @return string
     */
    public function getUserAgent()
    {
        return $this->userAgent;
    }

    /**
     * Set remoteHost.
     *
     * @param string $remoteHost
     *
     * @return VideoHit
     */
    public function setRemoteHost($remoteHost)
    {
        $this->remoteHost = $remoteHost;

        return $this;
    }

    /**
     * Get remoteHost.
     *
     * @return string
     */
    public function getRemoteHost()
    {
        return $this->remoteHost;
    }

    /**
     * Set ipAddress.
     *
     * @return VideoHit
     */
    public function setIpAddress(\Mautic\CoreBundle\Entity\IpAddress $ipAddress)
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    /**
     * Get ipAddress.
     *
     * @return \Mautic\CoreBundle\Entity\IpAddress
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    /**
     * Set pageLanguage.
     *
     * @param string $pageLanguage
     *
     * @return VideoHit
     */
    public function setPageLanguage($pageLanguage)
    {
        $this->pageLanguage = $pageLanguage;

        return $this;
    }

    /**
     * Get pageLanguage.
     *
     * @return string
     */
    public function getPageLanguage()
    {
        return $this->pageLanguage;
    }

    /**
     * Set browserLanguages.
     *
     * @param array<string> $browserLanguages
     *
     * @return VideoHit
     */
    public function setBrowserLanguages($browserLanguages)
    {
        $this->browserLanguages = $browserLanguages;

        return $this;
    }

    /**
     * Get browserLanguages.
     *
     * @return array<string>
     */
    public function getBrowserLanguages()
    {
        return $this->browserLanguages;
    }

    /**
     * @return Lead
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * @return VideoHit
     */
    public function setLead(Lead $lead)
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
     *
     * @return VideoHit
     */
    public function setChannel($channel)
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
     *
     * @return VideoHit
     */
    public function setChannelId($channelId)
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

    /**
     * @return VideoHit
     */
    public function setRedirect(Redirect $redirect)
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
     *
     * @return VideoHit
     */
    public function setQuery($query)
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

    /**
     * @return VideoHit
     */
    public function setTimeWatched($timeWatched)
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
     *
     * @return VideoHit
     */
    public function setGuid($guid)
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
     *
     * @return VideoHit
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;

        return $this;
    }
}
