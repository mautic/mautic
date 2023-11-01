<?php

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
     * @param bool $isNew
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
