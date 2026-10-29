<?php

namespace Mautic\CoreBundle\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class EnvironmentSubscriber
{
    public function __construct(
        private CoreParametersHelper $coreParametersHelper,
    ) {
    }

    #[AsEventListener(event: KernelEvents::REQUEST, priority: 128)]
    public function onKernelRequestSetTimezone(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!$request->hasPreviousSession()) {
            return;
        }

        // Set date/time
        date_default_timezone_set($request->getSession()->get('_timezone',
            $this->coreParametersHelper->getDefaultTimezone()));
    }

    /**
     * Set default locale.
     */
    #[AsEventListener(event: KernelEvents::REQUEST, priority: 101)]
    public function onKernelRequestSetLocale(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!$request->hasPreviousSession()) {
            return;
        }

        $locale = $request->getSession()->get('_locale');

        if (!$locale) {
            $locale = $this->coreParametersHelper->get('locale');
        }

        $request->setLocale($locale);
        $request->getSession()->set('_locale', $locale);
    }
}
