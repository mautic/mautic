<?php

namespace Mautic\WebhookBundle\Http;

use GuzzleHttp\Psr7\Request;
use Http\Adapter\Guzzle6\Client as GuzzleClient;
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
        GuzzleClient $httpClient
    ) {
        $this->coreParametersHelper = $coreParametersHelper;
        $this->httpClient           = $httpClient;
    }

    /**
     * @param string $url
     * @param string $secret
     *
     * @return ResponseInterface
     */
    public function post($url, array $payload, $secret)
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
