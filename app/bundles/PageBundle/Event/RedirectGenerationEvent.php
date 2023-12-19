<?php

namespace Mautic\PageBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\PageBundle\Entity\Redirect;

class RedirectGenerationEvent extends CommonEvent
{
    public function __construct(
        private Redirect $redirect,
        private array $clickthrough
    ) {
    }

    /**
     * Set or overwrite a value in the clickthrough.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function setInClickthrough($key, $value): void
    {
        $this->clickthrough[$key] = $value;
    }

    /**
     * Get the redirect from the event.
     *
     * @return Redirect
     */
    public function getRedirect()
    {
        return $this->redirect;
    }

    /**
     * Get the modified clickthrough from the event.
     *
     * @return array
     */
    public function getClickthrough()
    {
        return $this->clickthrough;
    }
}
