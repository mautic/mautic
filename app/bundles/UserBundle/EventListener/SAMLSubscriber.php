<?php

declare(strict_types=1);

namespace Mautic\UserBundle\EventListener;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

final readonly class SAMLSubscriber
{
    public function __construct(
        private RouterInterface $router,
    ) {
    }
    /**
     * Block access to SAML URLs if SAML is disabled.
     * This listener is removed from Kernel if SAML is not enabled. See mautic.saml_enabled parameter.
     */
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(
        event: KernelEvents::REQUEST,
        priority: 256
    )]
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route   = (string) $request->attributes->get('_route');
        $url     = $request->getRequestUri();
        if (!str_contains($route, 'lightsaml') && !str_contains($url, '/saml/')) {
            return;
        }

        // Redirect to standard login page if SAML is disabled
        $event->setResponse(
            new RedirectResponse($this->router->generate('login'))
        );
    }
}
