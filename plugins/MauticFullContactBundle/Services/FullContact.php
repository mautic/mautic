<?php

/**
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

function Services_FullContact_autoload($className)
{
    $library_name = 'Services_FullContact';

    if (substr($className, 0, strlen($library_name)) != $library_name) {
        return false;
    }
    $file = str_replace('_', '/', $className);
    $file = str_replace('Services/', '', $file);

    return include dirname(__FILE__)."/$file.php";
}

spl_autoload_register('Services_FullContact_autoload');

/**
 * This class handles the actually HTTP request to the FullContact endpoint.
 *
 * @package  Services\FullContact
 * @author   Keith Casey <contrib@caseysoftware.com>
 * @license  http://www.apache.org/licenses/LICENSE-2.0 Apache
 */
class Services_FullContact
{
    const REQUEST_LATENCY = 0.2;
    const USER_AGENT = 'caseysoftware/fullcontact-php-0.9.0';

    private $_next_req_time = null;

    protected $_baseUri = 'https://api.fullcontact.com/';
    protected $_version = 'v2';

    protected $_apiKey = null;
    protected $_webhookUrl = null;
    protected $_webhookId = null;
    protected $_supportedMethods = [];

    public $response_obj = null;
    public $response_code = null;
    public $response_json = null;

    /**
     * Slow down calls to the FullContact API if needed
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
     *
     * @param string $hdr
     */
    private function _update_rate_limit($hdr)
    {
        $remaining = (float)$hdr['X-Rate-Limit-Remaining'];
        $reset = (float)$hdr['X-Rate-Limit-Reset'];
        $spacing = $reset / (1.0 + $remaining);
        $delay = $spacing - self::REQUEST_LATENCY;
        $this->_next_req_time = new \DateTime('now + ' . $delay . ' seconds');
    }

    /**
     * The base constructor needs the API key available from here:
     * http://fullcontact.com/getkey
     *
     * @param type $api_key
     */
    public function __construct($api_key)
    {
        $this->_apiKey = $api_key;
        $this->_next_req_time = new \DateTime('@0');
    }

    /**
     * This sets the webhook url for all requests made for this service
     * instance. To unset, just use setWebhookUrl(null).
     *
     * @author  David Boskovic <me@david.gs> @dboskovic
     * @param   string $url
     * @param   string $id
     * @return  object
     */
    public function setWebhookUrl($url, $id = null)
    {
        $this->_webhookUrl = $url;
        $this->_webhookId = $id;

        return $this;
    }

    /**
     * This is a pretty close copy of my work on the Contactually PHP library
     *   available here: http://github.com/caseysoftware/contactually-php
     *
     * @author  Keith Casey <contrib@caseysoftware.com>
     * @author  David Boskovic <me@david.gs> @dboskovic
     * @param   array $params
     * @return  object
     * @throws \Services_FullContact_Exception_NoCredit
     * @throws  Services_FullContact_Exception_NotImplemented
     */
    protected function _execute($params = array())
    {
        if (!in_array($params['method'], $this->_supportedMethods, true)) {
            throw new Services_FullContact_Exception_NotImplemented(
                __CLASS__.
                " does not support the [".$params['method']."] method"
            );
        }

        $this->_wait_for_rate_limit();

        $params['apiKey'] = $this->_apiKey;

        if ($this->_webhookUrl) {
            $params['webhookUrl'] = $this->_webhookUrl;
        }

        if ($this->_webhookId) {
            $params['webhookId'] = $this->_webhookId;
        }

        $fullUrl = $this->_baseUri.$this->_version.$this->_resourceUri.
            '?'.http_build_query($params);

        //open connection
        $connection = curl_init($fullUrl);
        curl_setopt($connection, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($connection, CURLOPT_USERAGENT, self::USER_AGENT);
        curl_setopt($connection, CURLOPT_HEADER, 1); // return HTTP headers with response

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
                list ($key, $value) = explode(': ', $line);
                $headers[$key] = $value;
            }
        }

        $this->response_code = curl_getinfo($connection, CURLINFO_HTTP_CODE);
        $this->response_obj = json_decode($this->response_json);

        if ('403' === $this->response_code) {
            throw new Services_FullContact_Exception_NoCredit($this->response_obj->message);
        }

        $this->_update_rate_limit($headers);

        return $this->response_obj;
    }
}