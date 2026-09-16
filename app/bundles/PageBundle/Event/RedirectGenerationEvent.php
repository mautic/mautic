<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\PageBundle\Entity\Redirect;

final class RedirectGenerationEvent extends CommonEvent
{
    public function __construct(
        private readonly Redirect $redirect,
        private array $clickthrough,
    ) {
    }

    /**
     * Set or overwrite a value in the clickthrough.
     */
    public function setInClickthrough(string $key, string $value): void
    {
        $this->clickthrough[$key] = $value;
    }

    /**
     * Get the redirect from the event.
     */
    public function getRedirect(): Redirect
    {
        return $this->redirect;
    }

    /**
     * Get the modified clickthrough from the event.
     */
    public function getClickthrough(): array
    {
        return $this->clickthrough;
    }
}
