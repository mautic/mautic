<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCitrixBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use MauticPlugin\MauticCitrixBundle\Helper\CitrixHelper;
use MauticPlugin\MauticCitrixBundle\Model\CitrixModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class PublicController extends CommonController
{
    /**
     * This proxy is used for the GoToTraining API requests in order to bypass the CORS restrictions in AJAX.
     *
     * @param Request $request
     *
     * @return array|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     * @throws \InvalidArgumentException
     */
    public function proxyAction(Request $request)
    {
        $url = $request->query->get('url', null);
        if (!$url) {
            return $this->accessDenied(false, 'ERROR: url not specified');
        } else {
            /** @var IntegrationHelper $integrationHelper */
            $integrationHelper = $this->get('mautic.helper.integration');
            $myIntegration     = $integrationHelper->getIntegrationObject('Gototraining');

            if (!$myIntegration || !$myIntegration->getIntegrationSettings()->getIsPublished()) {
                return $this->accessDenied(false, 'ERROR: GoToTraining is not enabled');
            }

            $ch = curl_init($url);
            if (strtolower($request->server->get('REQUEST_METHOD', '')) === 'post') {
                $headers = [
                    'Content-type: application/json',
                    'Accept: application/json',
                ];
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request->request->all()));
            }
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, $request->server->get('HTTP_USER_AGENT', ''));
            list($header, $contents) = preg_split('/([\r\n][\r\n])\\1/', curl_exec($ch), 2);
            $status                  = curl_getinfo($ch);
            curl_close($ch);
        }

        // Set the JSON data object contents, decoding it from JSON if possible.
        $decoded_json = json_decode($contents);
        $data         = $decoded_json ?: $contents;

        // Generate JSON/JSONP string
        $json     = json_encode($data);
        $response = new Response($json, $status['http_code']);

        // Generate appropriate content-type header.
        $is_xhr = strtolower($request->server->get('HTTP_X_REQUESTED_WITH', null)) === 'xmlhttprequest';
        $response->headers->set('Content-type', 'application/'.($is_xhr ? 'json' : 'x-javascript'));

        // Allow CORS requests only from dev machines
        $allowedIps = $this->coreParametersHelper->getParameter('dev_hosts') ?: [];
        if (in_array($request->getClientIp(), $allowedIps, true)) {
            $response->headers->set('Access-Control-Allow-Origin', '*');
        }

        return $response;
    } // indexAction

    /**
     * This action will receive a POST when the session status changes.
     * A POST will also be made when a customer joins the session and when the session ends
     * (whether or not a customer joined).
     *
     * @param Request $request
     *
     * @return array|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     */
    public function sessionChangedAction(Request $request)
    {
        /** @var IntegrationHelper $integrationHelper */
        $integrationHelper = $this->get('mautic.helper.integration');
        $myIntegration     = $integrationHelper->getIntegrationObject('Gototraining');

        if (!$myIntegration || !$myIntegration->getIntegrationSettings()->getIsPublished()) {
            return $this->accessDenied(false, 'ERROR: GoToTraining is not enabled');
        }

        $post = $request->request->all();

        try {
            /** @var CitrixModel $citrixModel */
            $citrixModel = $this->get('mautic.model.factory')->getModel('citrix.citrix');
            $productId   = $post['sessionId'];
            $eventDesc   = sprintf('%s (%s)', $productId, $post['status']);
            $eventName   = CitrixHelper::getCleanString(
                    $eventDesc
                ).'_#'.$productId;
            $product = 'assist';
            $citrixModel->syncEvent($product, $productId, $eventName, $eventDesc);
        } catch (\Exception $ex) {
            throw new BadRequestHttpException($ex->getMessage());
        }

        return new Response('OK');
    } // sessionChangedAction
} // class
