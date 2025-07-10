<?php

namespace Mautic\UserBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

class SAMLSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private RouterInterface $router,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 256],
        ];
    }

    /**
     * Block access to SAML URLs if SAML is disabled.
     * This listener is removed from Kernel if SAML is not enabled. See mautic.saml_enabled parameter.
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route   = (string) $request->attributes->get('_route');
        $url     = (string) $request->getRequestUri();
        if (!str_contains($route, 'lightsaml') && !str_contains($url, '/saml/')) {
            return;
        }

        // Redirect to standard login page if SAML is disabled
        $event->setResponse(
            new RedirectResponse($this->router->generate('login'))
        );
    }
}
