<?php


namespace MauticPlugin\IntegrationsBundle\Tests\Auth\Provider\Oauth2ThreeLegged;


use GuzzleHttp\ClientInterface;
use kamermans\OAuth2\OAuth2Middleware;
use kamermans\OAuth2\Signer\AccessToken\SignerInterface as AccessTokenSigner;
use kamermans\OAuth2\Signer\ClientCredentials\SignerInterface as ClientCredentialsSigner;
use MauticPlugin\IntegrationsBundle\Auth\Provider\ConfigAccess\CredentialsSignerInterface;
use MauticPlugin\IntegrationsBundle\Auth\Provider\ConfigAccess\TokenPersistenceInterface;
use MauticPlugin\IntegrationsBundle\Auth\Provider\ConfigAccess\TokenSignerInterface;
use MauticPlugin\IntegrationsBundle\Auth\Provider\Oauth2ThreeLegged\ConfigInterface;
use MauticPlugin\IntegrationsBundle\Auth\Provider\Oauth2ThreeLegged\Credentials\CodeInterface;
use MauticPlugin\IntegrationsBundle\Auth\Provider\Oauth2ThreeLegged\Credentials\CredentialsInterface;
use MauticPlugin\IntegrationsBundle\Auth\Provider\Oauth2ThreeLegged\Credentials\RedirectUriInterface;
use MauticPlugin\IntegrationsBundle\Auth\Provider\Oauth2ThreeLegged\Credentials\ScopeInterface;
use MauticPlugin\IntegrationsBundle\Auth\Provider\Oauth2ThreeLegged\HttpFactory;
use MauticPlugin\IntegrationsBundle\Exception\PluginNotConfiguredException;

class HttpFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testType()
    {
        $this->assertEquals('oauth2_three_legged', (new HttpFactory())->getAuthType());
    }

    public function testMissingAuthorizationUrlThrowsException()
    {
        $this->expectException(PluginNotConfiguredException::class);

        $credentials = new Class implements CredentialsInterface
        {
            public function getAuthorizationUrl(): string
            {
                return '';
            }

            public function getTokenUrl(): string
            {
                return '';
            }

            public function getClientId(): ?string
            {
                return '';
            }

            public function getClientSecret(): ?string
            {
                return '';
            }
        };

        (new HttpFactory())->getClient($credentials);
    }

    public function testMissingTokenUrlThrowsException()
    {
        $this->expectException(PluginNotConfiguredException::class);

        $credentials = new Class implements CredentialsInterface
        {
            public function getAuthorizationUrl(): string
            {
                return 'http://auth.url';
            }

            public function getTokenUrl(): string
            {
                return '';
            }

            public function getClientId(): ?string
            {
                return '';
            }

            public function getClientSecret(): ?string
            {
                return '';
            }
        };

        (new HttpFactory())->getClient($credentials);
    }

    public function testMissingClientIdThrowsException()
    {
        $this->expectException(PluginNotConfiguredException::class);

        $credentials = new Class implements CredentialsInterface
        {
            public function getAuthorizationUrl(): string
            {
                return 'http://auth.url';
            }

            public function getTokenUrl(): string
            {
                return 'http://token.url';
            }

            public function getClientId(): ?string
            {
                return '';
            }

            public function getClientSecret(): ?string
            {
                return '';
            }
        };

        (new HttpFactory())->getClient($credentials);
    }

    public function testMissingClientSecretThrowsException()
    {
        $this->expectException(PluginNotConfiguredException::class);

        $credentials = new Class implements CredentialsInterface
        {
            public function getAuthorizationUrl(): string
            {
                return 'http://auth.url';
            }

            public function getTokenUrl(): string
            {
                return 'http://token.url';
            }

            public function getClientId(): ?string
            {
                return 'foo';
            }

            public function getClientSecret(): ?string
            {
                return '';
            }
        };

        (new HttpFactory())->getClient($credentials);
    }

    public function testInstantiatedClientIsReturned()
    {
        $credentials = new Class implements CredentialsInterface
        {
            public function getAuthorizationUrl(): string
            {
                return 'http://auth.url';
            }

            public function getTokenUrl(): string
            {
                return 'http://token.url';
            }

            public function getClientId(): ?string
            {
                return 'foo';
            }

            public function getClientSecret(): ?string
            {
                return 'bar';
            }
        };

        $factory = new HttpFactory();

        $client1 = $factory->getClient($credentials);
        $client2 = $factory->getClient($credentials);
        $this->assertTrue($client1 === $client2);

        $credentials2 = new Class implements CredentialsInterface
        {
            public function getAuthorizationUrl(): string
            {
                return 'http://auth.url';
            }

            public function getTokenUrl(): string
            {
                return 'http://token.url';
            }

            public function getClientId(): ?string
            {
                return 'bar';
            }

            public function getClientSecret(): ?string
            {
                return 'foo';
            }
        };

        $client3 = $factory->getClient($credentials2);
        $this->assertFalse($client1 === $client3);
    }

    public function testReAuthClientConfiguration()
    {
        $credentials = $this->getCredentials();

        $client = (new HttpFactory())->getClient($credentials);

        $middleware = $this->extractMiddleware($client);

        $reflectedMiddleware = new \ReflectionClass($middleware);
        $grantType           = $this->getProperty($reflectedMiddleware, $middleware, 'grantType');

        $reflectedGrantType = new \ReflectionClass($grantType);
        $reauthConfig       = $this->getProperty($reflectedGrantType, $grantType, 'config');

        $expectedConfig = [
            'client_id'     => $credentials->getClientId(),
            'client_secret' => $credentials->getClientSecret(),
            'code'          => $credentials->getCode(),
            'redirect_uri'  => $credentials->getRedirectUri(),
            'scope'         => $credentials->getScope(),
        ];

        $this->assertEquals($expectedConfig, $reauthConfig->toArray());
    }

    public function testClientConfiguration()
    {
        $credentials            = $this->getCredentials();
        $tokenPersistence       = $this->createMock(TokenPersistenceInterface::class);
        $accessTokenSigner      = $this->createMock(AccessTokenSigner::class);
        $clientCredentialSigner = $this->createMock(ClientCredentialsSigner::class);

        $config = new Class($tokenPersistence, $accessTokenSigner, $clientCredentialSigner) implements ConfigInterface
        {
            private $tokenPersistence;
            private $accessTokenSigner;
            private $clientCredentialSigner;

            /**
             *  constructor.
             *
             * @param $tokenPersistence
             * @param $accessTokenSigner
             * @param $clientCredentialSigner
             */
            public function __construct($tokenPersistence, $accessTokenSigner, $clientCredentialSigner)
            {
                $this->tokenPersistence       = $tokenPersistence;
                $this->accessTokenSigner      = $accessTokenSigner;
                $this->clientCredentialSigner = $clientCredentialSigner;
            }

            public function getAccessTokenPersistence(): TokenPersistenceInterface
            {
                return $this->tokenPersistence;
            }

            public function getAccessTokenSigner(): TokenSignerInterface
            {
                return $this->accessTokenSigner;
            }

            public function getClientCredentialsSigner(): CredentialsSignerInterface
            {
                return $this->clientCredentialSigner;
            }
        };

        $client = (new HttpFactory())->getClient($credentials, $config);

        $middleware = $this->extractMiddleware($client);

        $reflectedMiddleware = new \ReflectionClass($middleware);

        $clientCredentialSigner = $this->getProperty($reflectedMiddleware, $middleware, 'clientCredentialsSigner');
        $this->assertTrue($clientCredentialSigner === $config->getClientCredentialsSigner());

        $accessTokenSigner = $this->getProperty($reflectedMiddleware, $middleware, 'accessTokenSigner');
        $this->assertTrue($accessTokenSigner === $config->getAccessTokenSigner());

        $tokenPersistence = $this->getProperty($reflectedMiddleware, $middleware, 'tokenPersistence');
        $this->assertTrue($tokenPersistence === $config->getAccessTokenPersistence());
    }

    /**
     * @param ClientInterface $client
     *
     * @return OAuth2Middleware
     * @throws \ReflectionException
     */
    private function extractMiddleware(ClientInterface $client): OAuth2Middleware
    {
        $handler = $client->getConfig()['handler'];

        $reflection = new \ReflectionClass($handler);
        $property   = $reflection->getProperty('stack');
        $property->setAccessible(true);

        $stack = $property->getValue($handler);

        /** @var OAuth2Middleware $oauthMiddleware */
        $oauthMiddleware = array_pop($stack);

        return $oauthMiddleware[0];
    }

    private function getProperty(\ReflectionClass $reflection, $object, string $name)
    {
        $property = $reflection->getProperty($name);
        $property->setAccessible(true);

        return $property->getValue($object);
    }

    /**
     * @return CredentialsInterface|CodeInterface|RedirectUriInterface|ScopeInterface
     */
    private function getCredentials(): CredentialsInterface
    {
        return new Class implements CredentialsInterface, CodeInterface, RedirectUriInterface, ScopeInterface
        {
            public function getAuthorizationUrl(): string
            {
                return 'http://auth.url';
            }

            public function getTokenUrl(): string
            {
                return 'http://token.url';
            }

            public function getClientId(): ?string
            {
                return 'bar';
            }

            public function getClientSecret(): ?string
            {
                return 'foo';
            }

            public function getCode(): ?string
            {
                return 'auth_code';
            }

            public function getRedirectUri(): string
            {
                return 'http://redirect.url';
            }

            public function getScope(): ?string
            {
                return 'scope';
            }
        };
    }
}