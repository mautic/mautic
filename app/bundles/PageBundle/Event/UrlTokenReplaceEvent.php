<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Event;

use Mautic\LeadBundle\Entity\Lead;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched when replacing tokens in a URL, allowing modifications before the final redirect.
 */
final class UrlTokenReplaceEvent extends Event
{
    public function __construct(
        private string $content,
        private Lead $lead,
        private ?int $emailId = null,
    ) {
    }

    /**
     * Get the URL content.
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Set the URL content.
     */
    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    /**
     * Get the lead entity.
     */
    public function getLead(): Lead
    {
        return $this->lead;
    }

    /**
     * Get the email ID.
     */
    public function getEmailId(): ?int
    {
        return $this->emailId;
    }
}
