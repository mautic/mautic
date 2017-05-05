<?php

/*
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at.
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace MauticPlugin\MauticFullContactBundle\Services;

use MauticPlugin\MauticFullContactBundle\Exception\FullContact_Exception_NoCredit;
use MauticPlugin\MauticFullContactBundle\Exception\FullContact_Exception_NotImplemented;

/**
 * This class handles the actually HTTP request to the FullContact endpoint.
 *
 * @author   Keith Casey <contrib@caseysoftware.com>
 * @license  http://www.apache.org/licenses/LICENSE-2.0 Apache
 */
class FullContact_Base
{
    const REQUEST_LATENCY = 0.2;
    const USER_AGENT      = 'caseysoftware/fullcontact-php-0.9.0';

    private $_next_req_time = null;

//    protected $_baseUri = 'https://requestbin.fullcontact.com/1ailj6d1?';
    protected $_baseUri = 'https://api.fullcontact.com/';
    protected $_version = 'v2';

    protected $_apiKey           = null;
    protected $_webhookUrl       = null;
    protected $_webhookId        = null;
    protected $_webhookJson      = false;
    protected $_supportedMethods = [];

    public $response_obj  = null;
    public $response_code = null;
    public $response_json = null;

    /**
     * Slow down calls to the FullContact API if needed.
     */
    private function _wait_for_rate_limit()
    {
        $now = new \DateTime();
        if ($this->_next_req_time && $this->_next_req_time->getTimestamp() > $now->getTimestamp()) {
            $t = $this->_next_req_time->getTimestamp() - $now->getTimestamp();
            sleep($t);
        }
    }

    /**
     * @param string $hdr
     */
    private function _update_rate_limit($hdr)
    {
        $remaining            = (float) $hdr['X-Rate-Limit-Remaining'];
        $reset                = (float) $hdr['X-Rate-Limit-Reset'];
        $spacing              = $reset / (1.0 + $remaining);
        $delay                = $spacing - self::REQUEST_LATENCY;
        $this->_next_req_time = new \DateTime('now + '.$delay.' seconds');
    }

    /**
     * The base constructor Sets the API key available from here:
     * http://fullcontact.com/getkey.
     *
     * @param string $api_key
     */
    public function __construct($api_key)
    {
        $this->_apiKey        = $api_key;
        $this->_next_req_time = new \DateTime('@0');
    }

    /**
     * This sets the webhook url for all requests made for this service
     * instance. To unset, just use setWebhookUrl(null).
     *
     * @author  David Boskovic <me@david.gs> @dboskovic
     *
     * @param string $url
     * @param string $id
     * @param bool   $json
     *
     * @return object
     */
    public function setWebhookUrl($url, $id = null, $json = false)
    {
        $this->_webhookUrl  = $url;
        $this->_webhookId   = $id;
        $this->_webhookJson = $json;

        return $this;
    }

    /**
     * This is a pretty close copy of my work on the Contactually PHP library
     *   available here: http://github.com/caseysoftware/contactually-php.
     *
     * @author  Keith Casey <contrib@caseysoftware.com>
     * @author  David Boskovic <me@david.gs> @dboskovic
     *
     * @param array $params
     * @param array $postData
     *
     * @return object
     *
     * @throws FullContact_Exception_NoCredit
     * @throws FullContact_Exception_NotImplemented
     */
    protected function _execute($params = [], $postData = null)
    {
        if (null === $postData && !in_array($params['method'], $this->_supportedMethods, true)) {
            throw new FullContact_Exception_NotImplemented(
                __CLASS__.
                ' does not support the ['.$params['method'].'] method'
            );
        }

        if (array_key_exists('method', $params)) {
            unset($params['method']);
        }

        $this->_wait_for_rate_limit();

        $params['apiKey'] = $this->_apiKey;

        if ($this->_webhookUrl) {
            $params['webhookUrl'] = $this->_webhookUrl;
        }

        if ($this->_webhookId) {
            $params['webhookId'] = $this->_webhookId;
        }

        if ($this->_webhookJson) {
            $params['webhookBody'] = 'json';
        }

        $fullUrl = $this->_baseUri.$this->_version.$this->_resourceUri.
            '?'.http_build_query($params);

        //open connection
        $connection = curl_init($fullUrl);
        curl_setopt($connection, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($connection, CURLOPT_USERAGENT, self::USER_AGENT);
        curl_setopt($connection, CURLOPT_HEADER, 1); // return HTTP headers with response

        if (null !== $postData) {
            curl_setopt($connection, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($connection, CURLOPT_POSTFIELDS, json_encode($postData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            curl_setopt($connection, CURLOPT_POST, 1);
        }

        //execute request
        $resp = curl_exec($connection);

        list($response_headers, $this->response_json) = explode("\r\n\r\n", $resp, 2);
        // $response_headers now has a string of the HTTP headers
        // $response_json is the body of the HTTP response

        $headers = [];

        foreach (explode("\r\n", $response_headers) as $i => $line) {
            if ($i === 0) {
                $headers['http_code'] = $line;
            } else {
                list($key, $value) = explode(': ', $line);
                $headers[$key]     = $value;
            }
        }

        $this->response_code = curl_getinfo($connection, CURLINFO_HTTP_CODE);
        $this->response_obj  = json_decode($this->response_json);

        if ('403' === $this->response_code) {
            throw new FullContact_Exception_NoCredit($this->response_obj->message);
        } else {
            if ('200' === $this->response_code) {
                $this->_update_rate_limit($headers);
            }
        }

        return $this->response_obj;
    }
}
