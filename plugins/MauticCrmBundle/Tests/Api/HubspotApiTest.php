<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCrmBundle\Tests\Api;

use Mautic\PluginBundle\Exception\ApiErrorException;
use MauticPlugin\MauticCrmBundle\Api\HubspotApi;
use MauticPlugin\MauticCrmBundle\Integration\HubspotIntegration;

class HubspotApiTest extends \PHPUnit\Framework\TestCase
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

        $integration->expects($this->exactly(1))
            ->method('makeRequest')
            ->willReturn(
                [
                    'error' => [
                        'code'    => $code,
                        'message' => json_encode($response),
                    ],
                ]
            )
        ;

        $api = new HubspotApi($integration);
        try {
            $api->getLeadFields();

            $this->fail('ApiErrorException not thrown');
        } catch (ApiErrorException $exception) {
            $this->assertEquals($message, $exception->getMessage());
            $this->assertEquals($code, $exception->getCode());
        }
    }
}
