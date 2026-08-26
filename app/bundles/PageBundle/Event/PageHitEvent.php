<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Entity\Page;

class PageHitEvent extends CommonEvent
{
    protected ?Page $page = null;

    public function __construct(
        Hit $hit,
        protected $request,
        protected $code,
        protected array $clickthroughData = [],
        protected bool $unique = false,
    ) {
        $this->entity           = $hit;
        $this->page             = $hit->getPage();
    }

    /**
     * Returns the Page entity.
     */
    public function getPage(): ?Page
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

    public function getClickthroughData(): array
    {
        return $this->clickthroughData;
    }

    /**
     * Returns if this page hit is unique.
     */
    public function isUnique(): bool
    {
        return $this->unique;
    }
}
