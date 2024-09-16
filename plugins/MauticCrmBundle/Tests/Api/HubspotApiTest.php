<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCrmBundle\Tests\Api;

use Mautic\PluginBundle\Exception\ApiErrorException;
use MauticPlugin\MauticCrmBundle\Api\HubspotApi;
use MauticPlugin\MauticCrmBundle\Integration\HubspotIntegration;
use PHPUnit\Framework\TestCase;

class HubspotApiTest extends TestCase
{
    /**
     * @testdox Test Hubspot api when the api-key is invalid
     */
    public function testHubspotWhenKeyIsInvalid(): void
    {
        $integration = $this->createMock(HubspotIntegration::class);
        $message     = 'The API key provided is invalid. View or manage your API key here: https://app-eu1.hubspot.com/l/api-key/';
        $code        = 401;
        $response    = [
            'status'        => 'error',
            'message'       => $message,
            'correlationId' => '00000000-0000-0000-0000-000000000000',
            'category'      => 'INVALID_AUTHENTICATION',
            'links'         => [
                'api key' => 'https://app-eu1.hubspot.com/l/api-key/',
            ],
        ];

        $integration->expects(self::once())
            ->method('makeRequest')
            ->willReturn(
                [
                    'error' => [
                        'code'    => $code,
                        'message' => json_encode($response),
                    ],
                ]
            );
        $integration->expects(self::once())
            ->method('getAuthenticationType')
            ->willReturn('crm');

        $this->expectException(ApiErrorException::class);
        $this->expectExceptionMessage($message);
        $this->expectExceptionCode($code);

        $api = new HubspotApi($integration);
        $api->getLeadFields();

        self::fail('ApiErrorException not thrown');
    }

    public function testHubspotWhenKeyIsInvalidIfOauth(): void
    {
        $integration = $this->createMock(HubspotIntegration::class);
        $message     = 'The API key provided is invalid. View or manage your API key here: https://app-eu1.hubspot.com/l/api-key/';
        $response    = [
            'error'         => 'error',
            'code'          => 402,
            'message'       => $message,
            'correlationId' => '00000000-0000-0000-0000-000000000000',
            'category'      => 'INVALID_AUTHENTICATION',
            'links'         => [
                'api key' => 'https://app-eu1.hubspot.com/l/api-key/',
            ],
        ];

        $integration->expects(self::once())
            ->method('makeRequest')
            ->willReturn(['error' => $response]);
        $integration->expects(self::once())
            ->method('getAuthenticationType')
            ->willReturn('oauth2');

        $this->expectException(ApiErrorException::class);
        $this->expectExceptionMessage($message);
        $this->expectExceptionCode(0);

        $api = new HubspotApi($integration);
        $api->getLeadFields();

        self::fail('ApiErrorException not thrown');
    }
}
