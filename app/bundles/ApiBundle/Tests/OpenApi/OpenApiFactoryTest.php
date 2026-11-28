<?php

declare(strict_types=1);

namespace Mautic\ApiBundle\Tests\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Components;
use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\SecurityScheme;
use ApiPlatform\OpenApi\Model\Server;
use ApiPlatform\OpenApi\OpenApi;
use Mautic\ApiBundle\OpenApi\OpenApiFactory;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class OpenApiFactoryTest extends TestCase
{
    private OpenApiFactoryInterface&MockObject $decorated;

    private CoreParametersHelper&MockObject $coreParametersHelper;

    protected function setUp(): void
    {
        $this->decorated           = $this->createMock(OpenApiFactoryInterface::class);
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
    }

    #[DataProvider('basicAuthProvider')]
    public function testAddsMauticServerAndAuthentication(bool $basicAuthEnabled): void
    {
        $existingScheme = new SecurityScheme(type: 'apiKey', name: 'X-API-Key', in: 'header');
        $openApi        = new OpenApi(
            new Info('Mautic API', '1.0.0'),
            [new Server('/')],
            new Paths(),
            new Components(securitySchemes: new \ArrayObject(['existing' => $existingScheme])),
            [['existing' => []]],
        );

        $this->decorated->expects($this->once())
            ->method('__invoke')
            ->with(['spec_version' => '3.1.0'])
            ->willReturn($openApi);
        $this->coreParametersHelper->expects($this->atLeast(2))->method('get')
            ->willReturnMap([
                ['site_url', null, 'https://mautic.example.test/'],
                ['api_enable_basic_auth', null, $basicAuthEnabled],
            ]);

        $result = (new OpenApiFactory($this->decorated, $this->coreParametersHelper))([
            'spec_version' => '3.1.0',
        ]);

        $this->assertSame('https://mautic.example.test', $result->getServers()[0]->getUrl());
        $this->assertSame($existingScheme, $result->getComponents()->getSecuritySchemes()['existing']);

        $oauth2 = $result->getComponents()->getSecuritySchemes()['oauth2'];
        $this->assertSame('oauth2', $oauth2->getType());
        $this->assertSame('https://mautic.example.test/oauth/v2/authorize', $oauth2->getFlows()->getAuthorizationCode()->getAuthorizationUrl());
        $this->assertSame('https://mautic.example.test/oauth/v2/token', $oauth2->getFlows()->getAuthorizationCode()->getTokenUrl());
        $this->assertSame('https://mautic.example.test/oauth/v2/token', $oauth2->getFlows()->getClientCredentials()->getTokenUrl());

        $expectedSecurity = [
            ['existing' => []],
            ['oauth2' => []],
        ];

        if ($basicAuthEnabled) {
            $basicAuth = $result->getComponents()->getSecuritySchemes()['basicAuth'];
            $this->assertSame('http', $basicAuth->getType());
            $this->assertSame('basic', $basicAuth->getScheme());
            $expectedSecurity[] = ['basicAuth' => []];
        } else {
            $this->assertArrayNotHasKey('basicAuth', $result->getComponents()->getSecuritySchemes());
        }

        $this->assertSame($expectedSecurity, $result->getSecurity());
    }

    /**
     * @return iterable<string, array{bool}>
     */
    public static function basicAuthProvider(): iterable
    {
        yield 'basic auth enabled' => [true];
        yield 'basic auth disabled' => [false];
    }
}
