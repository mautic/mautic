<?php

namespace Mautic\LeadBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class UtmTag
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var \DateTimeInterface
     */
    private $dateAdded;

    /**
     * @var \Mautic\LeadBundle\Entity\Lead
     */
    private $lead;

    /**
     * @var array
     */
    private $query = [];

    /**
     * @var string|null
     */
    private $referer;

    /**
     * @var string|null
     */
    private $remoteHost;

    private $url;

    /**
     * @var string|null
     */
    private $userAgent;

    /**
     * @var string|null
     */
    private $utmCampaign;

    /**
     * @var string|null
     */
    private $utmContent;

    /**
     * @var string|null
     */
    private $utmMedium;

    /**
     * @var string|null
     */
    private $utmSource;

    /**
     * @var string|null
     */
    private $utmTerm;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('lead_utmtags');
        $builder->setCustomRepositoryClass(UtmTagRepository::class);
        $builder->addId();
        $builder->addDateAdded();
        $builder->addLead(false, 'CASCADE', false, 'utmtags');
        $builder->addNullableField('query', Types::ARRAY);
        $builder->addNullableField('referer', Types::TEXT);
        $builder->addNullableField('remoteHost', Types::STRING, 'remote_host');
        $builder->addNullableField('url', Types::TEXT);
        $builder->addNullableField('userAgent', Types::TEXT, 'user_agent');
        $builder->addNullableField('utmCampaign', Types::STRING, 'utm_campaign');
        $builder->addNullableField('utmContent', Types::STRING, 'utm_content');
        $builder->addNullableField('utmMedium', Types::STRING, 'utm_medium');
        $builder->addNullableField('utmSource', Types::STRING, 'utm_source');
        $builder->addNullableField('utmTerm', Types::STRING, 'utm_term');
    }

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
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set date added.
     *
     * @return UtmTag
     */
    public function setDateAdded(\DateTimeInterface $date)
    {
        $this->dateAdded = $date;

        return $this;
    }

    /**
     * Get date added.
     *
     * @return \DateTimeInterface
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * @return Lead
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * @return UtmTag
     */
    public function setLead(Lead $lead)
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
     *
     * @return UtmTag
     */
    public function setQuery($query)
    {
        $this->query = $query;

        return $this;
    }

    /**
     * Set referer.
     *
     * @param string $referer
     *
     * @return UtmTag
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
     * Set remoteHost.
     *
     * @param string $remoteHost
     *
     * @return UtmTag
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
     * Set url.
     *
     * @param string $url
     *
     * @return UtmTag
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
     * @return UtmTag
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
     * @return string
     */
    public function getUtmCampaign()
    {
        return $this->utmCampaign;
    }

    /**
     * @param string $utmCampaign
     *
     * @return UtmTag
     */
    public function setUtmCampaign($utmCampaign)
    {
        $this->utmCampaign = $utmCampaign;

        return $this;
    }

    /**
     * @return string
     */
    public function getUtmContent()
    {
        return $this->utmContent;
    }

    /**
     * @param string $utmContent
     *
     * @return UtmTag
     */
    public function setUtmContent($utmContent)
    {
        $utmContent       = mb_strlen($utmContent) <= ClassMetadataBuilder::MAX_VARCHAR_INDEXED_LENGTH ? $utmContent : mb_substr($utmContent, 0, ClassMetadataBuilder::MAX_VARCHAR_INDEXED_LENGTH);
        $this->utmContent = $utmContent;

        return $this;
    }

    /**
     * @return string
     */
    public function getUtmMedium()
    {
        return $this->utmMedium;
    }

    /**
     * @param string $utmMedium
     *
     * @return UtmTag
     */
    public function setUtmMedium($utmMedium)
    {
        $this->utmMedium = $utmMedium;

        return $this;
    }

    /**
     * @return string
     */
    public function getUtmSource()
    {
        return $this->utmSource;
    }

    /**
     * @param string $utmSource
     *
     * @return UtmTag
     */
    public function setUtmSource($utmSource)
    {
        $this->utmSource = $utmSource;

        return $this;
    }

    /**
     * @return string
     */
    public function getUtmTerm()
    {
        return $this->utmTerm;
    }

    /**
     * @param string $utmTerm
     *
     * @return UtmTag
     */
    public function setUtmTerm($utmTerm)
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
