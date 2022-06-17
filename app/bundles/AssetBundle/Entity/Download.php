<?php

namespace Mautic\AssetBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\EmailBundle\Entity\Email;

/**
 * Class Download.
 */
class Download
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var \DateTime
     */
    private $dateDownload;

    /**
     * @var Asset
     */
    private $asset;

    /**
     * @var \Mautic\CoreBundle\Entity\IpAddress
     */
    private $ipAddress;

    /**
     * @var \Mautic\LeadBundle\Entity\Lead
     */
    private $lead;

    /**
     * @var string
     */
    private $code;

    /**
     * @var string
     */
    private $referer;

    /**
     * @var string
     */
    private $trackingId;

    /**
     * @var string
     */
    private $source;

    /**
     * @var string
     */
    private $sourceId;

    /**
     * @var \Mautic\EmailBundle\Entity\Email
     */
    private $email;

    /**
     * @var string
     */
    private $utmCampaign;

    /**
     * @var string
     */
    private $utmContent;

    /**
     * @var string
     */
    private $utmMedium;

    /**
     * @var string
     */
    private $utmSource;

    /**
     * @var string
     */
    private $utmTerm;


    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('asset_downloads')
            ->setCustomRepositoryClass('Mautic\AssetBundle\Entity\DownloadRepository')
            ->addIndex(['tracking_id'], 'download_tracking_search')
            ->addIndex(['source', 'source_id'], 'download_source_search')
            ->addIndex(['date_download'], 'asset_date_download');

        $builder->addBigIntIdField();

        $builder->createField('dateDownload', 'datetime')
            ->columnName('date_download')
            ->build();

        $builder->createManyToOne('asset', 'Asset')
            ->addJoinColumn('asset_id', 'id', true, false, 'CASCADE')
            ->build();

        $builder->addIpAddress();

        $builder->addLead(true, 'SET NULL');

        $builder->addField('code', 'integer');

        $builder->createField('referer', 'text')
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

        $builder->createManyToOne('email', 'Mautic\EmailBundle\Entity\Email')
            ->addJoinColumn('email_id', 'id', true, false, 'SET NULL')
            ->build();

        $builder->createField('utmCampaign', 'string')
            ->columnName('utm_campaign')
            ->nullable()
            ->build();

        $builder->createField('utmContent', 'string')
            ->columnName('utm_content')
            ->nullable()
            ->build();

        $builder->createField('utmMedium', 'string')
            ->columnName('utm_medium')
            ->nullable()
            ->build();

        $builder->createField('utmSource', 'string')
            ->columnName('utm_source')
            ->nullable()
            ->build();

        $builder->createField('utmTerm', 'string')
            ->columnName('utm_term')
            ->nullable()
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
     * Set dateDownload.
     *
     * @param \DateTime $dateDownload
     *
     * @return Download
     */
    public function setDateDownload($dateDownload)
    {
        $this->dateDownload = $dateDownload;

        return $this;
    }

    /**
     * Get dateDownload.
     *
     * @return \DateTime
     */
    public function getDateDownload()
    {
        return $this->dateDownload;
    }

    /**
     * Set code.
     *
     * @param int $code
     *
     * @return Download
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
     * @return Download
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
     * Set asset.
     *
     * @param Asset $asset
     *
     * @return Download
     */
    public function setAsset(Asset $asset = null)
    {
        $this->asset = $asset;

        return $this;
    }

    /**
     * Get asset.
     *
     * @return Asset
     */
    public function getAsset()
    {
        return $this->asset;
    }

    /**
     * Set ipAddress.
     *
     * @return Download
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
     * Set trackingId.
     *
     * @param int $trackingId
     *
     * @return Download
     */
    public function setTrackingId($trackingId)
    {
        $this->trackingId = $trackingId;

        return $this;
    }

    /**
     * Get trackingId.
     *
     * @return int
     */
    public function getTrackingId()
    {
        return $this->trackingId;
    }

    /**
     * @return mixed
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * @param mixed $lead
     */
    public function setLead($lead)
    {
        $this->lead = $lead;
    }

    /**
     * @return mixed
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param mixed $source
     */
    public function setSource($source)
    {
        $this->source = $source;
    }

    /**
     * @return int
     */
    public function getSourceId()
    {
        return $this->sourceId;
    }

    /**
     * @param mixed $sourceId
     */
    public function setSourceId($sourceId)
    {
        $this->sourceId = (int) $sourceId;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     */
    public function setEmail(Email $email)
    {
        $this->email = $email;
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
     */
    public function setUtmContent($utmContent)
    {
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
     */
    public function setUtmTerm($utmTerm)
    {
        $this->utmTerm = $utmTerm;

        return $this;
    }
}
