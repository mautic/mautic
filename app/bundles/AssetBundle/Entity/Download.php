<?php

namespace Mautic\AssetBundle\Entity;

use Doctrine\DBAL\Types\Types;
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

    private ?string $utmCampaign;

    private ?string $utmContent;

    private ?string $utmMedium;

    private ?string $utmSource;

    private ?string $utmTerm;

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

        $builder->createField('utmCampaign', Types::STRING)
            ->columnName('utm_campaign')
            ->nullable()
            ->build();

        $builder->createField('utmContent', Types::STRING)
            ->columnName('utm_content')
            ->nullable()
            ->build();

        $builder->createField('utmMedium', Types::STRING)
            ->columnName('utm_medium')
            ->nullable()
            ->build();

        $builder->createField('utmSource', Types::STRING)
            ->columnName('utm_source')
            ->nullable()
            ->build();

        $builder->createField('utmTerm', Types::STRING)
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

    public function setUtmContent($utmContent): static
    {
        $this->utmContent = $utmContent;

        return $this;
    }

    public function getUtmMedium(): ?string
    {
        return $this->utmMedium;
    }

    public function setUtmMedium(string $utmMedium): static
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
}
