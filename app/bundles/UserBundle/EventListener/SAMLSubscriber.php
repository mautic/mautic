<?php

namespace Mautic\UserBundle\EventListener;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Router;

class SAMLSubscriber implements EventSubscriberInterface
{
    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    /**
     * @var Router
     */
    private $router;

    public function __construct(CoreParametersHelper $coreParametersHelper, Router $router)
    {
        $this->coreParametersHelper = $coreParametersHelper;
        $this->router               = $router;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 256],
        ];
    }

    /**
     * Block access to SAML URLs if SAML is disabled.
     */
    public function onKernelRequest(GetResponseEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route   = $request->attributes->get('_route');
        $url     = $request->getRequestUri();
        if (false === strpos($route, 'lightsaml') && false === strpos($url, '/saml/')) {
            return;
        }

        $samlEnabled = (bool) $this->coreParametersHelper->get('saml_idp_metadata');
        if ($samlEnabled) {
            return;
        }

        // Redirect to standard login page if SAML is disabled
        $event->setResponse(
            new RedirectResponse($this->router->generate('login'))
        );
    }
}
