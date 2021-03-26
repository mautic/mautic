<?php

/*
* @copyright   2019 Mautic, Inc. All rights reserved
* @author      Mautic, Inc.
*
* @link        https://mautic.com
*
* @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
*/

namespace Mautic\WebhookBundle\Http;

use GuzzleHttp\Psr7\Request;
use Http\Adapter\Guzzle7\Client as GuzzleClient;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Psr\Http\Message\ResponseInterface;

class Client
{
    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    /**
     * @var GuzzleClient
     */
    private $httpClient;

    public function __construct(
        CoreParametersHelper $coreParametersHelper,
        $httpClient
    ) {
        $this->coreParametersHelper = $coreParametersHelper;
        $this->httpClient           = $httpClient;
    }

    /**
     * @param string      $url
     * @param string|null $secret
     *
     * @return ResponseInterface
     */

    /**
     * @param $url
     * @param null $secret
     *
     * @return mixed|ResponseInterface
     *
     * @throws \Http\Client\Exception
     */
    public function post($url, array $payload, $secret = null)
    {
        $jsonPayload = json_encode($payload);
        $signature   = base64_encode(hash_hmac('sha256', $jsonPayload, $secret, true));
        $headers     = [
            'Content-Type'      => 'application/json',
            'X-Origin-Base-URL' => $this->coreParametersHelper->get('site_url'),
            'Webhook-Signature' => $signature,
        ];

        return $this->httpClient->sendRequest(new Request('POST', $url, $headers, $jsonPayload));
    }
}
