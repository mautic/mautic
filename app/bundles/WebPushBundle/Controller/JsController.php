<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\WebPushBundle\Controller;

use Camspiers\JsonPretty\JsonPretty;
use Mautic\CoreBundle\Controller\CommonController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class JsController extends CommonController
{
    /**
     * We can't user JsonResponse here, because
     * it improperly encodes the data array
     *
     * @return Response
     */
    public function manifestAction()
    {
        $data = array(
            'start_url' => '/',
            'gcm_sender_id' => 446150739532,
            'gcm_user_visible_only' => true
        );

        // @deprecated: Drop when pushing minimum php version to 5.4
        if (version_compare(PHP_VERSION, '5.4', '<')) {
            $jsonPretty = new JsonPretty;

            $data = $jsonPretty->prettify($data);
        } else {
            $data = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        return new Response(
            $data,
            200,
            array(
                'Content-Type' => 'application/json'
            )
        );
    }

    /**
     * @return Response
     */
    public function workerAction()
    {
        return new Response(
            "importScripts('https://cdn.onesignal.com/sdks/OneSignalSDK.js');",
            200,
            array(
                'Service-Worker-Allowed' => '/',
                'Content-Type' => 'application/javascript'
            )
        );
    }

    /**
     * @return Response
     */
    public function updaterAction()
    {
        return new Response(
            "importScripts('https://cdn.onesignal.com/sdks/OneSignalSDK.js');",
            200,
            array(
                'Service-Worker-Allowed' => '/',
                'Content-Type' => 'application/javascript'
            )
        );
    }

    /**
     * @return Response
     */
    public function embedAction()
    {
        $subscribeUrl = $this->generateUrl('mautic_webpush_popup', null, UrlGeneratorInterface::ABSOLUTE_URL);
        $subscribeTitle = 'Subscribe To Notifications';
        $width = 450;
        $height = 450;

        $js = <<<JS
var MauticWebPush = MauticWebPush || [];

MauticWebPush.popup = function () {
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
};

MauticWebPush.init = function() {
    var subscribeButton = document.getElementById('mautic-webpush-subscribe');

    if (subscribeButton) {
        subscribeButton.addEventListener('click', function() {MauticWebPush.popup();});
    }
};

MauticWebPush.ready = function(f) {/in/.test(document.readyState)?setTimeout('MauticWebPush.ready('+f+')',9):f()};

MauticWebPush.ready('MauticWebPush.init');
JS;
        return new Response($js, 200, array('Content-Type' => 'application/javascript'));
    }
}