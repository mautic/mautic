<?php

namespace Mautic\EmailBundle\Stats\FetchOptions;

use Mautic\StatsBundle\Event\Options\FetchOptions;

class EmailStatOptions extends FetchOptions
{
    private array $ids = [];

    /**
     * @var int|null
     */
    private $companyId;

    /**
     * @var int|null
     */
    private $campaignId;

    /**
     * @var int|null
     */
    private $segmentId;

    private array $filters = [];

    private bool $canViewOthers = false;

    /**
     * @var string
     */
    private $unit;

    /**
     * @return $this
     */
    public function setEmailIds(array $ids)
    {
        $this->ids = $ids;

        return $this;
    }

    /**
     * @return array
     */
    public function getEmailIds()
    {
        return $this->ids;
    }

    /**
     * @return int|null
     */
    public function getCompanyId()
    {
        return $this->companyId;
    }

    /**
     * @param int|null $companyId
     */
    public function setCompanyId($companyId): self
    {
        $this->companyId = $companyId;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getCampaignId()
    {
        return $this->campaignId;
    }

    /**
     * @param int|null $campaignId
     */
    public function setCampaignId($campaignId): self
    {
        $this->campaignId = $campaignId;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getSegmentId()
    {
        return $this->segmentId;
    }

    /**
     * @param int|null $segmentId
     */
    public function setSegmentId($segmentId): self
    {
        $this->segmentId = $segmentId;

        return $this;
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        return $this->filters;
    }

    public function setFilters(array $filters): self
    {
        $this->filters = $filters;

        return $this;
    }

    public function canViewOthers(): bool
    {
        return $this->canViewOthers;
    }

    /**
     * @param bool $canViewOthers
     */
    public function setCanViewOthers($canViewOthers): self
    {
        $this->canViewOthers = $canViewOthers;

        return $this;
    }

    /**
     * @return string
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * @param string $unit
     *
     * @return $this
     */
    public function setUnit($unit)
    {
        $this->unit = $unit;

        return $this;
    }
}
