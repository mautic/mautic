<?php

declare(strict_types=1);

namespace Mautic\ApiBundle\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\OAuthFlow;
use ApiPlatform\OpenApi\Model\OAuthFlows;
use ApiPlatform\OpenApi\Model\SecurityScheme;
use ApiPlatform\OpenApi\Model\Server;
use ApiPlatform\OpenApi\OpenApi;
use Mautic\CoreBundle\Helper\CoreParametersHelper;

final readonly class OpenApiFactory implements OpenApiFactoryInterface
{
    private const OAUTH2_SCHEME_NAME = 'oauth2';

    private const BASIC_AUTH_SCHEME_NAME = 'basicAuth';

    public function __construct(
        private OpenApiFactoryInterface $decorated,
        private CoreParametersHelper $coreParametersHelper,
    ) {
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);
        $siteUrl = rtrim((string) $this->coreParametersHelper->get('site_url'), '/');

        if ('' !== $siteUrl) {
            $openApi = $openApi->withServers([new Server($siteUrl)]);
        }

        $authorizationUrl = $siteUrl.'/oauth/v2/authorize';
        $tokenUrl         = $siteUrl.'/oauth/v2/token';
        $securitySchemes  = new \ArrayObject($openApi->getComponents()->getSecuritySchemes()?->getArrayCopy() ?? []);
        $security         = $openApi->getSecurity();

        $securitySchemes[self::OAUTH2_SCHEME_NAME] = new SecurityScheme(
            type: 'oauth2',
            description: 'OAuth 2.0 authorization code or client credentials authentication.',
            flows: new OAuthFlows(
                clientCredentials: new OAuthFlow(
                    tokenUrl: $tokenUrl,
                    scopes: new \ArrayObject(),
                ),
                authorizationCode: new OAuthFlow(
                    authorizationUrl: $authorizationUrl,
                    tokenUrl: $tokenUrl,
                    refreshUrl: $tokenUrl,
                    scopes: new \ArrayObject(),
                ),
            ),
        );
        $security[] = [self::OAUTH2_SCHEME_NAME => []];

        if ($this->coreParametersHelper->get('api_enable_basic_auth')) {
            $securitySchemes[self::BASIC_AUTH_SCHEME_NAME] = new SecurityScheme(
                type: 'http',
                description: 'HTTP Basic authentication.',
                scheme: 'basic',
            );
            $security[] = [self::BASIC_AUTH_SCHEME_NAME => []];
        }

        return $openApi
            ->withComponents($openApi->getComponents()->withSecuritySchemes($securitySchemes))
            ->withSecurity($security);
    }
}
