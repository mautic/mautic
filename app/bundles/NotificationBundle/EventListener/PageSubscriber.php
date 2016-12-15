<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\NotificationBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Templating\Helper\AssetsHelper;
use Mautic\PageBundle\Event\PageDisplayEvent;
use Mautic\PageBundle\PageEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class PageSubscriber.
 */
class PageSubscriber extends CommonSubscriber
{
    /**
     * @var CoreParametersHelper
     */
    protected $coreParametersHelper;

    /**
     * @var AssetsHelper
     */
    protected $assetsHelper;

    /**
     * PageSubscriber constructor.
     *
     * @param AssetsHelper         $assetsHelper
     * @param CoreParametersHelper $coreParametersHelper
     */
    public function __construct(AssetsHelper $assetsHelper, CoreParametersHelper $coreParametersHelper)
    {
        $this->assetsHelper         = $assetsHelper;
        $this->coreParametersHelper = $coreParametersHelper;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            PageEvents::PAGE_ON_DISPLAY => ['onPageDisplay', 0],
        ];
    }

    /**
     * @param PageDisplayEvent $event
     */
    public function onPageDisplay(PageDisplayEvent $event)
    {
        if (!$this->coreParametersHelper->getParameter('notification_landing_page_enabled')) {
            return;
        }
        $appId                      = $this->coreParametersHelper->getParameter('notification_app_id');
        $safariWebId                = $this->coreParametersHelper->getParameter('notification_safari_web_id');
        $welcomenotificationEnabled = $this->coreParametersHelper->getParameter('welcomenotification_enabled');

        $this->assetsHelper->addScript($this->router->generate('mautic_js', [], UrlGeneratorInterface::ABSOLUTE_URL), 'onPageDisplay_headClose', true, 'mautic_js');
        $this->assetsHelper->addScript('https://cdn.onesignal.com/sdks/OneSignalSDK.js', 'onPageDisplay_headClose');

        $manifestUrl = $this->router->generate('mautic_onesignal_manifest');
        $this->assetsHelper->addCustomDeclaration('<link rel="manifest" href="'.$manifestUrl.'" />', 'onPageDisplay_headClose');

        $leadAssociationUrl = $this->router->generate('mautic_subscribe_notification', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $welcomenotificationText = '';
        if (!$welcomenotificationEnabled) {
            $welcomenotificationText = 'welcomeNotification: { "disable": true },';
        }

        $oneSignalInit = <<<JS

    var OneSignal = OneSignal || [];
    
    OneSignal.push(["init", {
        appId: "{$appId}",
        safari_web_id: "{$safariWebId}",
        autoRegister: true,
        {$welcomenotificationText}
        notifyButton: {
            enable: false // Set to false to hide
        }
    }]);

    var postUserIdToMautic = function(userId) {
        var xhr = new XMLHttpRequest();

        xhr.open('post', '{$leadAssociationUrl}', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.send('osid=' + userId);
    };

    OneSignal.getUserId(function(userId) {
        if (! userId) {
            OneSignal.on('subscriptionChange', function(isSubscribed) {
                if (isSubscribed) {
                    OneSignal.getUserId(function(newUserId) {
                        postUserIdToMautic(newUserId);
                    });
                }
            });
        } else {
            postUserIdToMautic(userId);
        }
    });
    
    // Just to be sure we've grabbed the ID
    window.onbeforeunload = function() {
        OneSignal.getUserId(function(userId) {
            if (userId) {
                postUserIdToMautic(userId);
            }        
        });    
    };
JS;

        $this->assetsHelper->addScriptDeclaration($oneSignalInit, 'onPageDisplay_headClose');
    }
}
