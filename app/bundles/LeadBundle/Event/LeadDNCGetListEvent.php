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
 * Class LeadDNCGetListEvent.
 */
class LeadDNCGetListEvent extends CommonEvent
{
    /**
     *  @var array
     */
    protected $dncList;

    /**
     * @var null | string
     */
    protected $channel;

    /**
     * @var null | mixed
     */
    protected $contacts;

    /**
     * LeadDNCGetListEvent constructor.
     *
     * @param array $dncList
     * @param null  $channel
     * @param null  $contacts
     */
    public function __construct(array $dncList, $channel = null, $contacts = null)
    {
        $this->dncList  = $dncList;
        $this->channel  = $channel;
        $this->contacts = $contacts;
    }

    /**
     * Returns the array of dnc entries.
     *
     * @return array
     */
    public function getDNCList()
    {
        return $this->dncList;
    }

    /**
     * Sets the array of dncList entries.
     *
     * @param array $dncList
     */
    public function setDNCList(array $dncList)
    {
        $this->dncList = $dncList;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @return mixed
     */
    public function getContacts()
    {
        return $this->contacts;
    }
}
