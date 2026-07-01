<?php

namespace Mautic\PageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\EmailBundle\Entity\Email;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadDevice;
use Mautic\PageBundle\Validator\PageHit;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class Hit
{
    public const TABLE_NAME = 'page_hits';

    /**
     * @var string
     */
    private $id;

    /**
     * @var \DateTimeInterface
     */
    private $dateHit;

    /**
     * @var \DateTimeInterface
     */
    private $dateLeft;

    private ?Page $page = null;

    /**
     * @var Redirect|null
     */
    private $redirect;

    private ?Email $email = null;

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
    private $urlTitle;

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
     * @var string
     **/
    private $trackingId;

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
    private $query = [];

    /**
     * @var LeadDevice|null
     */
    private $device;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable(self::TABLE_NAME)
            ->setCustomRepositoryClass(HitRepository::class)
            ->addIndex(['tracking_id'], 'page_hit_tracking_search')
            ->addIndex(['code'], 'page_hit_code_search')
            ->addIndex(['source', 'source_id'], 'page_hit_source_search')
            ->addIndex(['date_hit', 'date_left'], 'date_hit_left_index')
            ->addIndexWithOptions(['url'], 'page_hit_url', ['lengths' => [0 => 128]]);

        $builder->addBigIntIdField();

        $builder->createField('dateHit', 'datetime')
            ->columnName('date_hit')
            ->build();

        $builder->createField('dateLeft', 'datetime')
            ->columnName('date_left')
            ->nullable()
            ->build();

        $builder->createManyToOne('page', 'Page')
            ->addJoinColumn('page_id', 'id', true, false, 'SET NULL')
            ->build();

        $builder->createManyToOne('redirect', 'Redirect')
            ->addJoinColumn('redirect_id', 'id', true, false, 'SET NULL')
            ->build();

        $builder->createManyToOne('email', Email::class)
            ->addJoinColumn('email_id', 'id', true, false, 'SET NULL')
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

        $builder->createField('urlTitle', 'string')
            ->columnName('url_title')
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

        $builder->createField('pageLanguage', 'string')
            ->columnName('page_language')
            ->nullable()
            ->build();

        $builder->createField('browserLanguages', 'array')
            ->columnName('browser_languages')
            ->nullable()
            ->build();

        $builder->createField('trackingId', 'string')
            ->columnName('tracking_id')
            ->build();

        $builder->createField('source', 'string')
            ->nullable()
            ->build();

        $builder->createField('sourceId', 'integer')
            ->columnName('source_id')
            ->nullable()
            ->build();

        $builder->addNullableField('query', 'array');

        $builder->createManyToOne('device', LeadDevice::class)
            ->addJoinColumn('device_id', 'id', true, false, 'SET NULL')
            ->cascadePersist()
            ->build();
    }

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

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addConstraint(new PageHit());
    }

    /**
     * Get id.
     */
    public function getId(): int
    {
        return (int) $this->id;
    }

    /**
     * Set dateHit.
     *
     * @param \DateTime $dateHit
     */
    public function setDateHit($dateHit): static
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
     */
    public function setDateLeft($dateLeft): static
    {
        $this->dateLeft = $dateLeft;

        return $this;
    }

    /**
     * Set country.
     *
     * @param string $country
     */
    public function setCountry($country): static
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
     */
    public function setRegion($region): static
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
     */
    public function setCity($city): static
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
     */
    public function setIsp($isp): static
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
     */
    public function setOrganization($organization): static
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
     */
    public function setCode($code): static
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
     */
    public function setReferer($referer): static
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
     */
    public function setUrl($url): static
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
     * Set url title.
     *
     * @param string $urlTitle
     */
    public function setUrlTitle($urlTitle): static
    {
        $urlTitle       = mb_strlen($urlTitle) <= 191 ? $urlTitle : mb_substr($urlTitle, 0, 191);
        $this->urlTitle = $urlTitle;

        return $this;
    }

    /**
     * Get url title.
     *
     * @return string
     */
    public function getUrlTitle()
    {
        return $this->urlTitle;
    }

    /**
     * Set userAgent.
     *
     * @param string $userAgent
     */
    public function setUserAgent($userAgent): static
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
     */
    public function setRemoteHost($remoteHost): static
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
     * Set page.
     */
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
     * Get trackingId.
     *
     * @return string|null
     */
    public function getTrackingId()
    {
        return $this->trackingId;
    }

    /**
     * Set pageLanguage.
     *
     * @param string $pageLanguage
     */
    public function setPageLanguage($pageLanguage): static
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
     */
    public function setBrowserLanguages($browserLanguages): static
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
