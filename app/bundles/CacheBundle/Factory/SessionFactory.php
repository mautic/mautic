<?php

declare(strict_types=1);

namespace Mautic\CacheBundle\Factory;

use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Factory service to provide a SessionInterface without direct dependency on RequestStack.
 */
class SessionFactory
{
    public function __construct(private RequestStack $requestStack)
    {
    }

    /**
     * Gets the session from RequestStack or null if no session is available.
     */
    public function getSession(): ?SessionInterface
    {
        try {
            return $this->requestStack->getSession();
        } catch (SessionNotFoundException $e) {
            return null;
        }
    }
}
