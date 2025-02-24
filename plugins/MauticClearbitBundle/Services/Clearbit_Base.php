<?php

namespace MauticPlugin\MauticClearbitBundle\Services;

/**
 * This class handles the actually HTTP request to the Clearbit endpoint.
 */
class Clearbit_Base
{
    public const REQUEST_LATENCY = 0.2;

    public const USER_AGENT      = 'mautic/clearbit-php-0.1.0';

    private \DateTime $_next_req_time;

    protected $_baseUri     = '';

    protected $_resourceUri = '';

    protected $_version     = 'v2';

    protected $_webhookId;

    public $response_obj;

    public $response_code;

    public $response_json;

    /**
     * Slow down calls to the Clearbit API if needed.
     */
    private function _wait_for_rate_limit(): void
    {
        $now = new \DateTime();
        if ($this->_next_req_time->getTimestamp() > $now->getTimestamp()) {
            $t = $this->_next_req_time->getTimestamp() - $now->getTimestamp();
            sleep($t);
        }
    }

    /**
     * @param string $hdr
     */
    private function _update_rate_limit($hdr): void
    {
        $remaining            = (float) $hdr['X-RateLimit-Remaining'];
        $reset                = (float) $hdr['X-RateLimit-Reset'];
        $spacing              = $reset / (1.0 + $remaining);
        $delay                = $spacing - self::REQUEST_LATENCY;
        $this->_next_req_time = new \DateTime('now + '.$delay.' seconds');
    }

    /**
     * The base constructor Sets the API key available from here:
     * https://dashboard.clearbit.com/keys.
     *
     * @param string $api_key
     */
    public function __construct(
        protected $api_key
    ) {
        $this->_next_req_time = new \DateTime('@0');
    }

    /**
     * @param string $id
     *
     * @return object
     */
    public function setWebhookId($id = null)
    {
        $this->_webhookId = $id;

        return $this;
    }

    /**
     * @param array $params
     *
     * @return object
     */
    protected function _execute($params = [])
    {
        $this->_wait_for_rate_limit();

        if ($this->_webhookId) {
            $params['webhook_id'] = $this->_webhookId;
        }

        $fullUrl = $this->_baseUri.$this->_version.$this->_resourceUri.
            '?'.http_build_query($params);

        // open connection
        $connection = curl_init($fullUrl);
        curl_setopt($connection, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($connection, CURLOPT_USERAGENT, self::USER_AGENT);
        curl_setopt($connection, CURLOPT_HEADER, 1); // return HTTP headers with response
        curl_setopt($connection, CURLOPT_HTTPHEADER, ['Authorization: Bearer '.$this->api_key]);

        // execute request
        $resp = curl_exec($connection);

        [$response_headers, $this->response_json] = explode("\r\n\r\n", $resp, 2);
        // $response_headers now has a string of the HTTP headers
        // $response_json is the body of the HTTP response

        $headers = [];

        foreach (explode("\r\n", $response_headers) as $i => $line) {
            if (0 === $i) {
                $headers['http_code'] = $line;
            } else {
                [$key, $value]     = explode(': ', $line);
                $headers[$key]     = $value;
            }
        }

        $this->response_code = curl_getinfo($connection, CURLINFO_HTTP_CODE);
        $this->response_obj  = json_decode($this->response_json);

        if (!in_array($this->response_code, [200, 201, 202], true)) {
            throw new \Exception($this->response_obj->error->message);
        } else {
            if ('200' === $this->response_code) {
                $this->_update_rate_limit($headers);
            }
        }

        return $this->response_obj;
    }
}
