<?php

namespace Mautic\PageBundle\Event;

use Mautic\PageBundle\Entity\Page;
use Symfony\Contracts\EventDispatcher\Event;

class PageDisplayEvent extends Event
{
    public function __construct(
        private mixed $content,
        private Page $page,
        private array $params = [],
        private bool $trackingDisabled = false
    ) {
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
     * Get page content.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set page content.
     *
     * @param string $content
     */
    public function setContent($content): void
    {
        $this->content = $content;
    }

    /**
     * Get params.
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Set params.
     *
     * @param array $params
     */
    public function setParams($params): void
    {
        $this->params = $params;
    }

    /** If tracking is disabled no record for user should be created. */
    public function isTrackingDisabled(): bool
    {
        return $this->trackingDisabled;
    }

    /** If tracking is disabled no record for user should be created. */
    public function setTrackingDisabled(bool $trackingDisabled = true): PageDisplayEvent
    {
        $this->trackingDisabled = $trackingDisabled;

        return $this;
    }
}
