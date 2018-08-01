<?php

namespace MauticPlugin\MauticIntegrationsBundle\Auth\Provider;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Mautic\PluginBundle\Helper\oAuthHelper;
use Mautic\PluginBundle\Integration\UnifiedIntegrationInterface;

class OAuth1aProvider
{
    protected $consumerKey;
    protected $consumerSecret;
    protected $token;
    protected $tokenSecret;

    /**
     * @TODO 
     */
    public function getAuthHeaders($uri, $params, $method)
    {
        $integration = new class(
            $this->consumerKey,
            $this->consumerSecret,
            $this->token
        ) implements UnifiedIntegrationInterface {
            protected $consumerKey;
            protected $consumerSecret;
            protected $token;

            public function __construct($consumerKey, $consumerSecret, $token)
            {
                $this->consumerKey = $consumerKey;
                $this->consumerSecret = $consumerSecret;
                $this->token = $token;
            }

            public function getAuthCallbackUrl()
            {
                return null;
            }

            public function getAuthTokenKey()
            {
                return 'access_token';
            }

            public function getClientIdKey()
            {
                return 'consumer_id';
            }

            public function getClientSecretKey()
            {
                return 'consumer_secret';
            }
            
            public function getDecryptedApiKeys()
            {
                return [
                    'access_token' => $this->token,
                    'consumer_id' => $this->consumerKey,
                    'consumer_secret' => $this->consumerSecret
                ];
            }
        };

        $oauthHelper = new oAuthHelper($integration, null, [
            'token_secret' => $this->tokenSecret,
            'double_encode_basestring_parameters' => false,
        ]);

        $headers     = [];
        $authHeaders = $oauthHelper->getAuthorizationHeader($uri, $params, $method);

        foreach ($authHeaders as $header) {
            list ($key, $value) = explode(':', $header, 2);

            $headers[$key] = $value;
        }

        return $headers;
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthType()
    {
        return 'oauth1a';
    }
    
    /**
     * @param string $consumerKey
     * 
     * @return $this
     */
    public function setConsumerKey(string $consumerKey): self
    {
        $this->consumerKey = $consumerKey;

        return $this;
    }

    /**
     * @param string $consumerSecret
     * 
     * @return $this
     */
    public function setConsumerSecret(string $consumerSecret): self
    {
        $this->consumerSecret = $consumerSecret;

        return $this;
    }

    /**
     * @param string $token
     * 
     * @return $this
     */
    public function setToken(string $token): self
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @param string $tokenSecret
     * 
     * @return $this
     */
    public function setTokenSecret(string $tokenSecret): self
    {
        $this->tokenSecret = $tokenSecret;

        return $this;
    }
}
