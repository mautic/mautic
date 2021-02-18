<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Entity\Result;

class CountResult
{
    /**
     * @var int
     */
    private $count;

    /**
     * @var int
     */
    private $minId;

    /**
     * @var int
     */
    private $maxId;

    /**
     * CountResult constructor.
     *
     * @param $count
     * @param $minId
     * @param $maxId
     */
    public function __construct($count, $minId, $maxId)
    {
        $this->count = (int) $count;
        $this->minId = (int) $minId;
        $this->maxId = (int) $maxId;
    }

    /**
     * @return int
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * @return int
     */
    public function getMinId()
    {
        return $this->minId;
    }

    /**
     * @return int
     */
    public function getMaxId()
    {
        return $this->maxId;
    }
}
