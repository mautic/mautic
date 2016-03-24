<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\WebPushBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class WebPushApiController
 *
 * @package Mautic\WebPushBundle\Controller\Api
 */
class WebPushApiController extends CommonApiController
{
    /**
     * Receive Web Push webhook
     *
     * @return Response
     */
    public function receiveAction()
    {
        /** @var \Mautic\WebPushBundle\Api\OneSignalApi $webpush */
        $webpush = $this->container->get('mautic.webpush.api');

        $response = $webpush->sendNotification('beb0e3a5-5df4-42c8-89a7-d8750543cfe3', 'Works fantastically!');

        var_dump($response);die;
    }
}
