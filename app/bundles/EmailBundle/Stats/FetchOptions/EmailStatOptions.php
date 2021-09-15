<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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

    /**
     * @return int|null
     */
    public function getCompanyId()
    {
        return $this->companyId;
    }

    /**
     * @param int|null $companyId
     *
     * @return $this;
     */
    public function setCompanyId($companyId)
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
     *
     * @return $this;
     */
    public function setCampaignId($campaignId)
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
     *
     * @return $this;
     */
    public function setSegmentId($segmentId)
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
