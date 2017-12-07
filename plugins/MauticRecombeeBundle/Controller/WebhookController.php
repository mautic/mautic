<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticRecombeeBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class WebhookController extends CommonAjaxController
{
    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function processAction(Request $request)
    {
        $recombeeHelper = $this->get('mautic.recombee.helper');
        $params         = $request->request->all();
        // $this->parseRequest();
        return new Response('test');
    }

    private function log($message, $type = 'info')
    {
        $prefix  = 'webhookLog_';
        $file    = $type.'.log';
        $date    = new \DateTime();
        $message = json_decode($message, true);
        error_log($date->format('Y-m-d H:i:s').' '.print_r($message['mautic.page_on_hit'], true)."\n\n", 3, $prefix.$file);
        // file_put_contents($prefix . $file, $message);
    }

    /**
     * Get the request JSON object and log the request.
     *
     * @return object
     */
    private function parseRequest()
    {
        $rawData = file_get_contents('php://input');
        $this->log($rawData, 'request');

        return json_decode($rawData);
    }
}
