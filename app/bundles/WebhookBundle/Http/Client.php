<?php

namespace Mautic\WebhookBundle\Http;

use GuzzleHttp\Psr7\Request;
use Http\Adapter\Guzzle7\Client as GuzzleClient;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Psr\Http\Message\ResponseInterface;

class Client
{
    /**
     * @param GuzzleClient $httpClient
     */
    public function __construct(
        private CoreParametersHelper $coreParametersHelper,
        private $httpClient
    ) {
    }

    /**
     * @param string $url
     *
     * @return ResponseInterface
     */

    /**
     * @return mixed|ResponseInterface
     *
     * @throws \Http\Client\Exception
     */
    public function post($url, array $payload, string $secret = null)
    {
        $jsonPayload = json_encode($payload);
        $signature   = null === $secret ? null : base64_encode(hash_hmac('sha256', $jsonPayload, $secret, true));
        $headers     = [
            'Content-Type'      => 'application/json',
            'X-Origin-Base-URL' => $this->coreParametersHelper->get('site_url'),
            'Webhook-Signature' => $signature,
        ];

        return $this->httpClient->sendRequest(new Request('POST', $url, $headers, $jsonPayload));
    }
}
