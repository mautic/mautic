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

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\BuildJsEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\NotificationBundle\Helper\NotificationHelper;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class BuildJsSubscriber.
 */
class BuildJsSubscriber extends CommonSubscriber
{
    /**
     * @var NotificationHelper
     */
    protected $notificationHelper;

    /**
     * @var IntegrationHelper
     */
    protected $integrationHelper;

    /**
     * BuildJsSubscriber constructor.
     *
     * @param NotificationHelper $notificationHelper
     * @param IntegrationHelper  $integrationHelper
     */
    public function __construct(NotificationHelper $notificationHelper, IntegrationHelper $integrationHelper)
    {
        $this->notificationHelper = $notificationHelper;
        $this->integrationHelper  = $integrationHelper;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            CoreEvents::BUILD_MAUTIC_JS => ['onBuildJs', 254],
        ];
    }

    /**
     * @param BuildJsEvent $event
     */
    public function onBuildJs(BuildJsEvent $event)
    {
        $integration = $this->integrationHelper->getIntegrationObject('OneSignal');

        if (!$integration || $integration->getIntegrationSettings()->getIsPublished() === false) {
            return;
        }

        $subscribeUrl   = $this->router->generate('mautic_notification_popup', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $subscribeTitle = 'Subscribe To Notifications';
        $width          = 450;
        $height         = 450;

        $js = <<<JS
        
        {$this->notificationHelper->getHeaderScript()}
       
MauticJS.notification = {
    init: function () {
        
        {$this->notificationHelper->getScript()}
         
        var subscribeButton = document.getElementById('mautic-notification-subscribe');

        if (subscribeButton) {
            subscribeButton.addEventListener('click', MauticJS.notification.popup);
        }
    },

    popup: function () {
        var subscribeUrl = '{$subscribeUrl}';
        var subscribeTitle = '{$subscribeTitle}';
        var w = {$width};
        var h = {$height};

        // Fixes dual-screen position                         Most browsers      Firefox
        var dualScreenLeft = window.screenLeft != undefined ? window.screenLeft : screen.left;
        var dualScreenTop = window.screenTop != undefined ? window.screenTop : screen.top;

        var width = window.innerWidth ? window.innerWidth : document.documentElement.clientWidth ? document.documentElement.clientWidth : screen.width;
        var height = window.innerHeight ? window.innerHeight : document.documentElement.clientHeight ? document.documentElement.clientHeight : screen.height;

        var left = ((width / 2) - (w / 2)) + dualScreenLeft;
        var top = ((height / 2) - (h / 2)) + dualScreenTop;

        var subscribeWindow = window.open(
            subscribeUrl,
            subscribeTitle,
            'scrollbars=yes, width=' + w + ',height=' + h + ',top=' + top + ',left=' + left + ',directories=0,titlebar=0,toolbar=0,location=0,status=0,menubar=0,scrollbars=no,resizable=no'
        );

        if (window.focus) {
            subscribeWindow.focus();
        }
        
        window.closeSubscribeWindow = function() { subscribeWindow.close(); };
    }
};

MauticJS.documentReady(MauticJS.notification.init);
JS;

        $event->appendJs($js, 'Mautic Notification JS');
    }
}
