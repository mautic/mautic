<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\NotificationBundle\Controller;

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
            'gcm_sender_id' => '446150739532',
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
}