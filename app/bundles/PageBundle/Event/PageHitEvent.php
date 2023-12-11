<?php

namespace Mautic\PageBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Entity\Page;

class PageHitEvent extends CommonEvent
{
    protected ?\Mautic\PageBundle\Entity\Page $page = null;

    /**
     * @param mixed[] $clickthroughData
     * @param bool    $unique
     */
    public function __construct(
        Hit $hit,
        protected $request,
        protected $code,
        protected $clickthroughData = [],
        protected $unique = false
    ) {
        $this->entity           = $hit;
        $this->page             = $hit->getPage();
    }

    /**
     * Returns the Page entity.
     *
     * @return Page
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * Get page request.
     *
     * @return string
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Get HTML code.
     *
     * @return mixed
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return Hit
     */
    public function getHit()
    {
        return $this->entity;
    }

    /**
     * @return mixed
     */
    public function getClickthroughData()
    {
        return $this->clickthroughData;
    }

    /**
     * Returns if this page hit is unique.
     *
     * @return bool
     */
    public function isUnique()
    {
        return $this->unique;
    }
}
