<?php

namespace Mautic\CoreBundle\EventListener;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class EnvironmentSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private CoreParametersHelper $coreParametersHelper,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [
                // Cannot be called earlier than priority 128 or the session is not populated leading to Doctrine's UTCDateTimeType leaving
                // entity DateTime values in UTC
                ['onKernelRequestSetTimezone', 128],
                // Must be 101 to load after Symfony's default Locale listener
                ['onKernelRequestSetLocale', 101],
            ],
        ];
    }

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
