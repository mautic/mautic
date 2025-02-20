<?php

namespace Mautic\UserBundle\Event;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

class AuthenticationContentEvent extends Event
{
    /**
     * @var array
     */
    protected $content = [];

    /**
     * @var bool
     */
    protected $postLogout = false;

    public function __construct(
        protected Request $request,
    ) {
        $this->postLogout = $request->getSession()->get('post_logout', false);
    }

    /**
     * @return Request
     */
    public function getRequest()
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
