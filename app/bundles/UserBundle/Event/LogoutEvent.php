<?php

namespace Mautic\UserBundle\Event;

use Mautic\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

class LogoutEvent extends Event
{
    private array $session = [];

    public function __construct(
        private readonly User $user,
        private readonly Request $request,
    ) {
    }

    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * Add value to session after it's been cleared.
     */
    public function setPostSessionItem($key, $value): void
    {
        $this->session[$key] = $value;
    }

    /**
     * Get session items to be added after session has been cleared.
     */
    public function getPostSessionItems(): array
    {
        return $this->session;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }
}
