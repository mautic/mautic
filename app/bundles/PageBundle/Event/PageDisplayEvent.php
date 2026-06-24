<?php

namespace Mautic\PageBundle\Event;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\Page;
use Symfony\Contracts\EventDispatcher\Event;

class PageDisplayEvent extends Event
{
    /**
     * Preferred lead to be used in listeners.
     */
    private ?Lead $lead = null;

    public function __construct(
        private string $content,
        private Page $page,
        private array $params = [],
    ) {
    }

    /**
     * Returns the Page entity.
     */
    public function getPage(): Page
    {
        return $this->page;
    }

    /**
     * Get page content.
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Set page content.
     */
    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    /**
     * Get params.
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Set params.
     */
    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    public function getLead(): ?Lead
    {
        return $this->lead;
    }

    public function setLead(Lead $lead): void
    {
        $this->lead = $lead;
    }
}
