<?php

namespace Mautic\CoreBundle\EventListener;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class EnvironmentSubscriber
{
    public function __construct(
        private CoreParametersHelper $coreParametersHelper,
    ) {
    }

    // priority 127 so this runs after Symfony's session_listener (priority 128);
    // hasPreviousSession() needs the session started first
    #[AsEventListener(
        event: KernelEvents::REQUEST,
        priority: 127
    )]
    public function onKernelRequestSetTimezone(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!$request->hasPreviousSession()) {
            return;
        }

        // Set date/time
        $timezone = $request->getSession()->get('_timezone', $this->coreParametersHelper->getDefaultTimezone());
        date_default_timezone_set($timezone);
        DateTimeHelper::setLocalTimezone($timezone);
    }

    /**
     * Set default locale.
     */
    #[AsEventListener(
        event: KernelEvents::REQUEST,
        priority: 101
    )]
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
