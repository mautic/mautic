<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\EventListener;

use Mautic\CoreBundle\Helper\CookieHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class EnvironmentSubscriber implements EventSubscriberInterface
{
    /**
     * System params.
     *
     * @var array
     */
    private $params;

    public function __construct(CookieHelper $cookieHelper, array $params)
    {
        $this->params       = $params;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => [
                // Cannot be called earlier than priority 128 or the session is not populated leading to Doctrine's UTCDateTimeType leaving
                // entity DateTime values in UTC
                ['onKernelRequestSetTimezone', 128],
                // Must be 15 to load after Symfony's default Locale listener
                ['onKernelRequestSetLocale', 15],
            ],
        ];
    }

    /**
     * Set timezone.
     */
    public function onKernelRequestSetTimezone(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        if (!$request->hasPreviousSession()) {
            return;
        }

        // Set date/time
        date_default_timezone_set($request->getSession()->get('_timezone', $this->params['default_timezone']));
    }

    /**
     * Set default locale.
     */
    public function onKernelRequestSetLocale(GetResponseEvent $event)
    {
        // Set the user's default locale
        $request = $event->getRequest();
        if (!$request->hasPreviousSession()) {
            return;
        }

        // Set locale
        if ($locale = $request->attributes->get('_locale')) {
            $request->getSession()->set('_locale', $locale);
        } else {
            $request->setLocale($request->getSession()->get('_locale', $this->params['locale']));
        }
    }
}
