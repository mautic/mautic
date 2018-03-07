<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;

/**
 * Class ListPreProcessListEvent.
 */
class ListPreProcessListEvent extends CommonEvent
{
    /**
     * @var array
     */
    protected $list;

    protected $result;

    /**
     * @param array $list
     * @param bool  $isNew
     */
    public function __construct(array $list, $isNew = false)
    {
        $this->list  = $list;
        $this->isNew = $isNew;
    }

    /**
     * Returns the List entity.
     *
     * @return array
     */
    public function getList()
    {
        return $this->list;
    }

    /**
     * Sets the lead list entity.
     *
     * @param array $list
     */
    public function setList(array $list)
    {
        $this->list = $list;
    }

    /**
     * @return bool
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param $result
     *
     * @return $this
     */
    public function setResult($result)
    {
        $this->result = $result;

        return $this;
    }
}
