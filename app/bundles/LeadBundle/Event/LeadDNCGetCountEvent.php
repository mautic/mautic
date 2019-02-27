<?php
/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;

/**
 * Class LeadDNCGetCountEvent.
 */
class LeadDNCGetCountEvent extends CommonEvent
{
    /**
     * @var array
     */
    protected $dncCount;

    /**
     * @var null | string
     */
    protected $channel;

    /**
     * @var null | array
     */
    protected $ids;

    /**
     * @var null | int
     */
    protected $reason;

    /**
     * @var null | int
     */
    protected $listId;

    /**
     * @var bool
     */
    protected $combined;

    /**
     * LeadDNCGetCountEvent constructor.
     *
     * @param array $dncEntities
     * @param null  $channel
     * @param null  $ids
     * @param null  $reason
     * @param null  $listId
     * @param bool  $combined
     */
    public function __construct(array $dncCount, $channel = null, $ids = null, $reason = null, $listId = null, $combined = false)
    {
        $this->dncCount = $dncCount;
        $this->channel  = $channel;
        $this->ids      = $ids;
        $this->reason   = $reason;
        $this->listId   = $listId;
        $this->combined = $combined;
    }

    /**
     * Returns the DNCEntities count.
     *
     * @return int | array
     */
    public function getDNCCount()
    {
        return $this->dncCount;
    }

    /**
     * Sets the DNCEntities Count.
     *
     * @param int | array
     */
    public function setDNCCount(int $dncCount)
    {
        $this->dncCount = $dncCount;
    }

    /**
     * Returns the channel.
     *
     * @return mixed | null
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * Returns the ids.
     *
     * @return mixed | null
     */
    public function getIds()
    {
        return $this->ids;
    }

    /**
     * Returns the reason int.
     *
     * @return int | null
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * Returns the list id.
     *
     * @return int | null
     */
    public function getListId()
    {
        return $this->listId;
    }

    /**
     * Returns the combined bool.
     *
     * @return bool
     */
    public function getCombined()
    {
        return $this->combined;
    }
}
