<?php


namespace MauticPlugin\IntegrationsBundle\Tests\Auth\Provider\Oauth2TwoLegged;


use GuzzleHttp\ClientInterface;
use kamermans\OAuth2\GrantType\ClientCredentials;
use kamermans\OAuth2\GrantType\PasswordCredentials;
use kamermans\OAuth2\OAuth2Middleware;
use kamermans\OAuth2\Persistence\TokenPersistenceInterface;
use kamermans\OAuth2\Signer\AccessToken\SignerInterface as AccessTokenSigner;
use kamermans\OAuth2\Signer\ClientCredentials\SignerInterface as ClientCredentialsSigner;
use MauticPlugin\IntegrationsBundle\Auth\Provider\AuthCredentialsInterface;
use MauticPlugin\IntegrationsBundle\Auth\Provider\Oauth2TwoLegged\ConfigInterface;
use MauticPlugin\IntegrationsBundle\Auth\Provider\Oauth2TwoLegged\Credentials\ClientCredentialsGrantInterface;
use MauticPlugin\IntegrationsBundle\Auth\Provider\Oauth2TwoLegged\Credentials\PasswordCredentialsGrantInterface;
use MauticPlugin\IntegrationsBundle\Auth\Provider\Oauth2TwoLegged\Credentials\ScopeInterface;
use MauticPlugin\IntegrationsBundle\Auth\Provider\Oauth2TwoLegged\Credentials\StateInterface;
use MauticPlugin\IntegrationsBundle\Auth\Provider\Oauth2TwoLegged\HttpFactory;
use MauticPlugin\IntegrationsBundle\Exception\InvalidCredentialsException;
use MauticPlugin\IntegrationsBundle\Exception\PluginNotConfiguredException;

class HttpFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testType()
    {
        $this->assertEquals('oauth2_two_legged', (new HttpFactory())->getAuthType());
    }

    public function testInvalidCredentialsThrowsException()
    {
        $this->expectException(InvalidCredentialsException::class);

        $credentials = new Class implements AuthCredentialsInterface
        {
        };

        (new HttpFactory())->getClient($credentials);
    }

    public function testMissingAuthorizationUrlThrowsException()
    {
        $this->expectException(PluginNotConfiguredException::class);

        $credentials = new Class implements ClientCredentialsGrantInterface
        {
            public function getAuthorizationUrl(): string
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

        $credentials = new Class implements ClientCredentialsGrantInterface
        {
            public function getAuthorizationUrl(): string
            {
                return 'http://test.com';
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

    public function testMissingClientSecretIdThrowsException()
    {
        $this->expectException(PluginNotConfiguredException::class);

        $credentials = new Class implements ClientCredentialsGrantInterface
        {
            public function getAuthorizationUrl(): string
            {
                return 'http://test.com';
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

    public function testMissingUsernameThrowsException()
    {
        $this->expectException(PluginNotConfiguredException::class);

        $credentials = new Class implements PasswordCredentialsGrantInterface
        {
            public function getAuthorizationUrl(): string
            {
                return 'http://test.com';
            }

            public function getClientId(): ?string
            {
                return 'foo';
            }

            public function getClientSecret(): ?string
            {
                return 'bar';
            }

            public function getUsername(): ?string
            {
                return '';
            }

            public function getPassword(): ?string
            {
                return '';
            }
        };

        (new HttpFactory())->getClient($credentials);
    }

    public function testMissingPasswordThrowsException()
    {
        $this->expectException(PluginNotConfiguredException::class);

        $credentials = new Class implements PasswordCredentialsGrantInterface
        {
            public function getAuthorizationUrl(): string
            {
                return 'http://test.com';
            }

            public function getClientId(): ?string
            {
                return 'foo';
            }

            public function getClientSecret(): ?string
            {
                return 'bar';
            }

            public function getUsername(): ?string
            {
                return 'foo';
            }

            public function getPassword(): ?string
            {
                return '';
            }
        };

        (new HttpFactory())->getClient($credentials);
    }


    public function testInstantiatedClientIsReturned()
    {
        $credentials = new Class implements ClientCredentialsGrantInterface
        {
            public function getAuthorizationUrl(): string
            {
                return 'http://test.com';
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

        $credentials2 = new Class implements ClientCredentialsGrantInterface
        {
            public function getAuthorizationUrl(): string
            {
                return 'http://test.com';
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
            'scope'         => $credentials->getScope(),
            'state'         => $credentials->getState(),
            'username'      => $credentials->getUsername(),
            'password'      => $credentials->getPassword(),
        ];

        $this->assertEquals($expectedConfig, $reauthConfig->toArray());
    }

    public function testPasswordGrantTypeIsUsed()
    {
        $credentials = new Class implements PasswordCredentialsGrantInterface
        {
            public function getAuthorizationUrl(): string
            {
                return 'http://test.com';
            }

            public function getClientId(): ?string
            {
                return 'foo';
            }

            public function getClientSecret(): ?string
            {
                return 'bar';
            }

            public function getUsername(): ?string
            {
                return 'username';
            }

            public function getPassword(): ?string
            {
                return 'password';
            }
        };

        $client              = (new HttpFactory())->getClient($credentials);
        $middleware          = $this->extractMiddleware($client);
        $reflectedMiddleware = new \ReflectionClass($middleware);
        $grantType           = $this->getProperty($reflectedMiddleware, $middleware, 'grantType');

        $this->assertInstanceOf(PasswordCredentials::class, $grantType);
    }

    public function testClientCredentialsGrantTypeIsUsed()
    {
        $credentials = new Class implements ClientCredentialsGrantInterface
        {
            public function getAuthorizationUrl(): string
            {
                return 'http://test.com';
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

        $client              = (new HttpFactory())->getClient($credentials);
        $middleware          = $this->extractMiddleware($client);
        $reflectedMiddleware = new \ReflectionClass($middleware);
        $grantType           = $this->getProperty($reflectedMiddleware, $middleware, 'grantType');

        $this->assertInstanceOf(ClientCredentials::class, $grantType);
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

            public function getAccessTokenSigner(): AccessTokenSigner
            {
                return $this->accessTokenSigner;
            }

            public function getClientCredentialsSigner(): ClientCredentialsSigner
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
     * @return PasswordCredentialsGrantInterface
     */
    private function getCredentials(): PasswordCredentialsGrantInterface
    {
        return new Class implements PasswordCredentialsGrantInterface, StateInterface, ScopeInterface
        {
            public function getAuthorizationUrl(): string
            {
                return 'http://test.com';
            }

            public function getClientId(): ?string
            {
                return 'foo';
            }

            public function getClientSecret(): ?string
            {
                return 'bar';
            }

            public function getUsername(): ?string
            {
                return 'username';
            }

            public function getPassword(): ?string
            {
                return 'password';
            }

            public function getState(): ?string
            {
                return 'state';
            }

            public function getScope(): ?string
            {
                return 'scope';
            }
        };
    }
}