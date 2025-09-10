<?php

namespace Mautic\WebhookBundle\Http;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Request;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PrivateAddressChecker;
use Mautic\WebhookBundle\Exception\PrivateAddressException;
use Psr\Http\Message\ResponseInterface;

class Client
{
    public function __construct(
        private CoreParametersHelper $coreParametersHelper,
        private GuzzleClient $httpClient,
        private PrivateAddressChecker $privateAddressChecker,
    ) {
    }

    /**
     * @throws \Http\Client\Exception
     */
    public function post($url, array $payload, string $secret = null): ResponseInterface
    {
        $jsonPayload = json_encode($payload);
        $signature   = null === $secret ? null : base64_encode(hash_hmac('sha256', $jsonPayload, $secret, true));
        $headers     = [
            'Content-Type'      => 'application/json',
            'X-Origin-Base-URL' => $this->coreParametersHelper->get('site_url'),
            'Webhook-Signature' => $signature,
        ];

        $allowedPrivateAddresses = $this->coreParametersHelper->get('webhook_allowed_private_addresses');
        $this->privateAddressChecker->setAllowedPrivateAddresses($allowedPrivateAddresses);

        if (!$this->privateAddressChecker->isAllowedUrl($url)) {
            throw new PrivateAddressException();
        }

        return $this->httpClient->sendRequest(new Request('POST', $url, $headers, $jsonPayload));
    }
}
