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
use MauticPlugin\MauticRecombeeBundle\Helper\RecombeeHelper;
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
        /** @var RecombeeHelper $recombeeHelper */
        $recombeeHelper = $this->get('mautic.recombee.helper');
        $params         = $request->request->all();
        $data = json_decode(file_get_contents('php://input'), true);
        $ret = $recombeeHelper->pushLead($data['mautic.lead_post_save_update']['mautic.lead_post_save_update'][0]['lead']);
        $this->log($ret);
        return new Response('test');
    }

    private function log($message, $type = 'info')
    {
        $prefix  = 'webhookLog_';
        $file    = $type.'.log';
        $date    = new \DateTime();
        error_log($date->format('Y-m-d H:i:s').' '.$message."\n\n", 3, $prefix.$file);
         file_put_contents($prefix . $file, $message);
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
