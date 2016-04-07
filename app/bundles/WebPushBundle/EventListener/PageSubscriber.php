<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\WebPushBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\PageBundle\Event\PageDisplayEvent;
use Mautic\PageBundle\PageEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class PageSubscriber
 *
 * @package Mautic\WebPushBundle\EventListener
 */
class PageSubscriber extends CommonSubscriber
{
    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            PageEvents::PAGE_ON_DISPLAY => array('onPageDisplay', 0)
        );
    }

    /**
     * @param PageDisplayEvent $event
     */
    public function onPageDisplay(PageDisplayEvent $event)
    {
        if (! $this->factory->getParameter('webpush_enabled')) {
            return;
        }

        $router = $this->factory->getRouter();
        $appId = $this->factory->getParameter('webpush_app_id', 'ab44aea7-ebe8-4bf4-bb7c-aa47e22d0364');

        /** @var \Mautic\CoreBundle\Templating\Helper\AssetsHelper $assetsHelper */
        $assetsHelper = $this->factory->getHelper('template.assets');

        $assetsHelper->addScript($router->generate('mautic_js'), 'onPageDisplay_headClose', true);
        $assetsHelper->addScript('https://cdn.onesignal.com/sdks/OneSignalSDK.js', 'onPageDisplay_headClose');

        $manifestUrl = $router->generate('mautic_onesignal_manifest');
        $assetsHelper->addCustomDeclaration('<link rel="manifest" href="' . $manifestUrl . '" />', 'onPageDisplay_headClose');

        $oneSignalInit = <<<JS

    var OneSignal = OneSignal || [];
    OneSignal.push(["init", {
        appId: "{$appId}",
        safari_web_id: 'web.onesignal.auto.31ba082c-c81b-42a5-be17-ec59d526e60e',
        autoRegister: true,
        notifyButton: {
            enable: false // Set to false to hide
        }
    }]);
JS;

        $assetsHelper->addScriptDeclaration($oneSignalInit, 'onPageDisplay_headClose');

    }
}
