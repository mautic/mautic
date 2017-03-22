<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\LeadBundle\Entity\Lead;

/**
 * Class VideoHit.
 */
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
     * @var \DateTime
     */
    private $dateHit;

    /**
     * @var \DateTime
     */
    private $dateLeft;

    /**
     * @var int
     */
    private $timeWatched;

    /**
     * @var int
     */
    private $duration;

    /**
     * @var Redirect
     */
    private $redirect;

    /**
     * @var \Mautic\LeadBundle\Entity\Lead
     */
    private $lead;

    /**
     * @var \Mautic\CoreBundle\Entity\IpAddress
     */
    private $ipAddress;

    /**
     * @var string
     */
    private $country;

    /**
     * @var string
     */
    private $region;

    /**
     * @var string
     */
    private $city;

    /**
     * @var string
     */
    private $isp;

    /**
     * @var string
     */
    private $organization;

    /**
     * @var int
     */
    private $code;

    /**
     * @var
     */
    private $referer;

    /**
     * @var
     */
    private $url;

    /**
     * @var string
     */
    private $userAgent;

    /**
     * @var string
     */
    private $remoteHost;

    /**
     * @var string
     */
    private $pageLanguage;

    /**
     * @var string
     */
    private $browserLanguages = [];

    /**
     * @var string
     */
    private $channel;

    /**
     * @var int
     */
    private $channelId;

    /**
     * @var array
     */
    private $query = [];

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('video_hits')
            ->setCustomRepositoryClass('Mautic\PageBundle\Entity\VideoHitRepository')
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
     *
     * @param $metadata
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata)
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
     * @return \DateTime
     */
    public function getDateHit()
    {
        return $this->dateHit;
    }

    /**
     * @return \DateTime
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
     * @param \Mautic\CoreBundle\Entity\IpAddress $ipAddress
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
     * @param string $browserLanguages
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
     * @return string
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
     * @param Lead $lead
     *
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
     * @param Redirect $redirect
     *
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
     * @param $timeWatched
     *
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
