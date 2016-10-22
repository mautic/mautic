<?php
/**
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCitrixBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicController extends CommonController
{

    public function proxyAction(Request $request)
    {
        $url = $request->query->get('url', null);

        if (!$url) {

            // Passed url not specified.
            return $this->accessDenied(false, 'ERROR: url not specified');

        } else {

            $ch = curl_init($url);

            if (strtolower($request->server->get('REQUEST_METHOD', '')) === 'post') {
                $headers = array(
                    'Content-type: application/json',
                    'Accept: application/json'
                );
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request->request->all()));
            }

            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, $request->server->get('HTTP_USER_AGENT', ''));

            list($header, $contents) = preg_split('/([\r\n][\r\n])\\1/', curl_exec($ch), 2);

            $status = curl_getinfo($ch);

            curl_close($ch);

        }

        // Set the JSON data object contents, decoding it from JSON if possible.
        $decoded_json = json_decode($contents);
        $data = $decoded_json ?: $contents;

        // Generate JSON/JSONP string
        $json = json_encode($data);

        $response = new Response($json, $status['http_code']);

        // Generate appropriate content-type header.
        $is_xhr = strtolower($request->server->get('HTTP_X_REQUESTED_WITH', null)) === 'xmlhttprequest';
        $response->headers->set('Content-type', 'application/'.($is_xhr ? 'json' : 'x-javascript'));
        $response->headers->set('Access-Control-Allow-Origin', '*');

        return $response;

    } // indexAction

} // class

