<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\EventListener;

use Mautic\CoreBundle\Controller\MauticController;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\MenuEvent;
use Mautic\CoreBundle\Event\RouteEvent;
use Mautic\CoreBundle\Event\IconEvent;
use Mautic\ApiBundle\Event as ApiEvents;
use Mautic\CoreBundle\Helper\BundleHelper;
use Mautic\CoreBundle\Helper\CookieHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Menu\MenuHelper;
use Mautic\CoreBundle\Templating\Helper\AssetsHelper;
use Mautic\InstallBundle\Controller\InstallController;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Event\LoginEvent;
use Mautic\UserBundle\Model\UserModel;
use Mautic\UserBundle\UserEvents;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

/**
 * Class EnvironmentSubscriber
 */
class EnvironmentSubscriber extends CommonSubscriber
{
    /**
     * @var CookieHelper
     */
    protected $cookieHelper;

    /**
     * @var CoreParametersHelper
     */
    protected $coreParametersHelper;

    public function __construct(CookieHelper $cookieHelper)
    {
        $this->cookieHelper = $cookieHelper;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => [
                ['onKernelRequestSetTimezone', 9999],
                ['onKernelRequestSetLocale', 15], // Must be 15 to load after Symfony's default Locale listener
            ],
        ];
    }

    /**
     * Set timezone
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequestSetTimezone(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        if (!$request->hasPreviousSession()) {
            return;
        }

        // Set date/time
        date_default_timezone_set($request->getSession()->get('_timezone', $this->params['default_timezone']));

        // Set a cookie with session name for filemanager
        $sessionName = $request->cookies->get('mautic_session_name');
        if ($sessionName != session_name()) {
            $this->cookieHelper->setCookie('mautic_session_name', session_name(), null);
        }
    }

    /**
     * Set default locale
     *
     * @param GetResponseEvent $event
     *
     * @return void
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
