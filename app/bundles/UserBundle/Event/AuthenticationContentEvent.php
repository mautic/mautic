<?php

namespace Mautic\UserBundle\Event;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

final class AuthenticationContentEvent extends Event
{
    private array $content = [];

    /**
     * @var bool
     */
    private $postLogout = false;

    public function __construct(
        private readonly Request $request,
    ) {
        $this->postLogout = $request->getSession()->get('post_logout', false);
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * @return bool
     */
    public function isLogout()
    {
        return $this->postLogout;
    }

    public function addContent($content): void
    {
        $this->content[] = $content;
    }

    public function getContent(): string
    {
        return implode("\n\n", $this->content);
    }
}
