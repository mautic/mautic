<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Unit\Auth\Provider\ApiKey;

use GuzzleHttp\Exception\ConnectException;
use Mautic\IntegrationsBundle\Auth\Provider\ApiKey\Credentials\HeaderCredentialsInterface;
use Mautic\IntegrationsBundle\Auth\Provider\ApiKey\Credentials\ParameterCredentialsInterface;
use Mautic\IntegrationsBundle\Auth\Provider\ApiKey\HttpFactory;
use Mautic\IntegrationsBundle\Auth\Provider\AuthCredentialsInterface;
use Mautic\IntegrationsBundle\Exception\InvalidCredentialsException;
use Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException;
use PHPUnit\Framework\TestCase;

class HttpFactoryTest extends TestCase
{
    public function testType(): void
    {
        $this->assertEquals('api_key', (new HttpFactory())->getAuthType());
    }

    public function testInvalidCredentialsThrowsException(): void
    {
        $this->expectException(InvalidCredentialsException::class);

        $credentials = new class implements AuthCredentialsInterface {
        };

        (new HttpFactory())->getClient($credentials);
    }

    public function testMissingCredentialsThrowsException(): void
    {
        $this->expectException(PluginNotConfiguredException::class);

        $credentials = new class implements HeaderCredentialsInterface {
            public function getApiKey(): string
            {
                return '';
            }

            public function getKeyName(): string
            {
                return '';
            }
        };

        (new HttpFactory())->getClient($credentials);
    }

    public function testInstantiatedClientIsReturned(): void
    {
        $credentials = new class implements HeaderCredentialsInterface {
            public function getApiKey(): string
            {
                return 'abc';
            }

            public function getKeyName(): string
            {
                return '123';
            }
        };

        $factory = new HttpFactory();

        $client1 = $factory->getClient($credentials);
        $client2 = $factory->getClient($credentials);
        $this->assertTrue($client1 === $client2);

        $credential2 = new class implements HeaderCredentialsInterface {
            public function getApiKey(): string
            {
                return '123';
            }

            public function getKeyName(): string
            {
                return 'abc';
            }
        };

        $client3 = $factory->getClient($credential2);
        $this->assertFalse($client1 === $client3);
    }

    public function testHeaderCredentialsSetsHeader(): void
    {
        $credentials = new class implements HeaderCredentialsInterface {
            public function getApiKey(): string
            {
                return '123';
            }

            public function getKeyName(): string
            {
                return 'abc';
            }
        };

        $factory = new HttpFactory();

        $client  = $factory->getClient($credentials);
        $headers = $client->getConfig('headers'); /** @phpstan-ignore-line Deprecated. Must be refactored for Guzzle 8 */
        $this->assertArrayHasKey('abc', $headers);
        $this->assertEquals('123', $headers['abc']);
    }

    public function testParameterCredentialsAppendsToken(): void
    {
        $credentials = new class implements ParameterCredentialsInterface {
            public function getApiKey(): string
            {
                return '123';
            }

            public function getKeyName(): string
            {
                return 'abc';
            }
        };

        $factory = new HttpFactory();
        $client  = $factory->getClient($credentials);

        try {
            // Triggering an exception so we can extract the request
            $client->request('get', 'foobar');
        } catch (ConnectException $exception) {
            $query = $exception->getRequest()->getUri()->getQuery();
            $this->assertEquals('abc=123', $query);
        }
    }
}
