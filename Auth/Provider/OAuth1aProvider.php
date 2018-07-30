<?php

namespace MauticPlugin\MauticIntegrations\AuthProvider;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Mautic\AuthenticationBundle\Integration\NeedsAuthorizationInterface;

class OAuth1aProvider implements AuthProviderInterface
{
    use AuthProviderTrait;

    /**
     * @var Client
     */
    protected $http;

    protected $accessToken;

    /**
     * @param Client $http
     */
    public function __construct(Client $http)
    {
        $this->http = $http;

        $this->setAuthKeys([
            'consumer_id',
            'consumer_secret',
        ]);
    }

    protected function getAuthHeaders()
    {
        return [
            'Authorization' => 'OAuth '.$this->accessToken,
        ];
    }

    protected function getAuthQueryParameters()
    {
        return [
            'oauth_callback' => '',
            'oauth_consumer_key' => '',
            'oauth_nonce' => $this->generateNonce(),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_signature' => '',
            'oauth_timestamp' => time(),
            'oauth_version' => '1.0'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthType()
    {
        return 'oauth1a';
    }
}
