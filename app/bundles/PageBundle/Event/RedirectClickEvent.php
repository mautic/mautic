<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Event;

use Mautic\PageBundle\Entity\Redirect;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

final class RedirectClickEvent extends Event
{
    public function __construct(
        private readonly Redirect $redirect,
        private readonly Request $request,
    ) {
    }

    public function getRedirect(): Redirect
    {
        return $this->redirect;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }
}
