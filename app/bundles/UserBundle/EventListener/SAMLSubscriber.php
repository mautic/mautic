<?php

namespace Mautic\UserBundle\EventListener;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

class SAMLSubscriber implements EventSubscriberInterface
{
    private CoreParametersHelper $coreParametersHelper;

    private RouterInterface $router;

    public function __construct(CoreParametersHelper $coreParametersHelper, RouterInterface $router)
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
    public function onKernelRequest(RequestEvent $event): void
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
