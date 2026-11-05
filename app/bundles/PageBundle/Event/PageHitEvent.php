<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Entity\Page;
use Symfony\Component\HttpFoundation\Request;

final class PageHitEvent extends CommonEvent
{
    private readonly ?Page $page;

    public function __construct(
        Hit $hit,
        private readonly Request $request,
        private $code,
        private readonly array $clickthroughData = [],
        private readonly bool $unique = false,
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
     */
    public function getRequest(): Request
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
