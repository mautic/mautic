<?php

namespace Mautic\EmailBundle\Stats\FetchOptions;

use Mautic\StatsBundle\Event\Options\FetchOptions;

class EmailStatOptions extends FetchOptions
{
    /**
     * @var array
     */
    private $ids = [];

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

    /**
     * @var array
     */
    private $filters = [];

    /**
     * @var bool
     */
    private $canViewOthers = false;

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

    public function getCompanyId(): ?int
    {
        return $this->companyId;
    }

    /**
     * @return $this;
     */
    public function setCompanyId(?int $companyId)
    {
        $this->companyId = $companyId;

        return $this;
    }

    public function getCampaignId(): ?int
    {
        return $this->campaignId;
    }

    /**
     * @return $this;
     */
    public function setCampaignId(?int $campaignId)
    {
        $this->campaignId = $campaignId;

        return $this;
    }

    public function getSegmentId(): ?int
    {
        return $this->segmentId;
    }

    /**
     * @return $this;
     */
    public function setSegmentId(?int $segmentId)
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

    /**
     * @return $this
     */
    public function setFilters(array $filters)
    {
        $this->filters = $filters;

        return $this;
    }

    /**
     * @return bool
     */
    public function canViewOthers()
    {
        return $this->canViewOthers;
    }

    /**
     * @param bool $canViewOthers
     *
     * @return $this
     */
    public function setCanViewOthers($canViewOthers)
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
