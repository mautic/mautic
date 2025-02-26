<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCrmBundle\Tests\Api;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\CacheStorageHelper;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Exception\ApiErrorException;
use MauticPlugin\MauticCrmBundle\Api\SalesforceApi;
use MauticPlugin\MauticCrmBundle\Integration\SalesforceIntegration;
use Symfony\Contracts\Translation\TranslatorInterface;

class SalesforceApiTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @testdox Test that a locked record request is retried up to 3 times
     */
    public function testRecordLockedErrorIsRetriedThreeTimes(): void
    {
        $integration = $this->createMock(SalesforceIntegration::class);
        $message     = 'unable to obtain exclusive access to this record or 1 records: 70137000000Ugy3AAC';

        $integration->expects($this->exactly(3))
            ->method('makeRequest')
            ->willReturn(
                [
                    [
                        'errorCode' => 'UNABLE_TO_LOCK_ROW',
                        'message'   => $message,
                    ],
                ]
            );

        $api = new SalesforceApi($integration);

        try {
            $api->request('/test');

            $this->fail('ApiErrorException not thrown');
        } catch (ApiErrorException $exception) {
            $this->assertEquals($message, $exception->getMessage());
        }
    }

    /**
     * @testdox Test that a locked record request is retried up to 3 times with last one being successful so no exception should be thrown
     */
    public function testRecordLockedErrorIsRetriedThreeTimesWithLastOneSuccessful(): void
    {
        $integration = $this->createMock(SalesforceIntegration::class);
        $message     = 'unable to obtain exclusive access to this record or 1 records: 70137000000Ugy3AAC';

        $integration->expects($this->exactly(3))
            ->method('makeRequest')
            ->willReturnOnConsecutiveCalls(
                [
                    [
                        'errorCode' => 'UNABLE_TO_LOCK_ROW',
                        'message'   => $message,
                    ],
                ],
                [
                    [
                        'errorCode' => 'UNABLE_TO_LOCK_ROW',
                        'message'   => $message,
                    ],
                ],
                [
                    [
                        'success' => true,
                    ],
                ]
            );

        $api = new SalesforceApi($integration);

        try {
            $api->request('/test');
        } catch (ApiErrorException) {
            $this->fail('ApiErrorException thrown');
        }
    }

    /**
     * @testdox Test that a locked record request is retried 2 times with 3rd being successful
     */
    public function testRecordLockedErrorIsRetriedTwoTimesWithThirdSuccess(): void
    {
        $integration = $this->createMock(SalesforceIntegration::class);
        $message     = 'unable to obtain exclusive access to this record or 1 records: 70137000000Ugy3AAC';

        $integration->expects($this->exactly(2))
            ->method('makeRequest')
            ->willReturnOnConsecutiveCalls(
                [
                    [
                        'errorCode' => 'UNABLE_TO_LOCK_ROW',
                        'message'   => $message,
                    ],
                ],
                [
                    [
                        ['success' => true],
                    ],
                ]
            );

        $api = new SalesforceApi($integration);

        try {
            $api->request('/test');
        } catch (ApiErrorException) {
            $this->fail('ApiErrorException should not have been thrown');
        }
    }

    /**
     * @testdox Test that a session expired should attempt a refresh before failing
     */
    public function testSessionExpiredIsRefreshed(): void
    {
        $integration = $this->createMock(SalesforceIntegration::class);
        $message     = '["errorCode":"INVALID_SESSION_ID","body":"Session expired or invalid"]';

        $integration->expects($this->exactly(2))
            ->method('authCallback');

        $integration->expects($this->exactly(2))
            ->method('makeRequest')
            ->willReturn(
                [
                    [
                        'message'   => $message,
                    ],
                ]
            );

        $api = new SalesforceApi($integration);

        try {
            $api->request('/test');
            $this->fail('ApiErrorException not thrown');
        } catch (ApiErrorException $exception) {
            $this->assertEquals($message, $exception->getMessage());
        }
    }

    /**
     * @testdox Test that a session expired should attempt a refresh but not throw an exception if successful on second request
     */
    public function testSessionExpiredIsRefreshedWithoutThrowingExceptionOnSecondRequestWithSuccess(): void
    {
        $integration = $this->createMock(SalesforceIntegration::class);
        $message     = 'Session expired';

        $integration->expects($this->once())
            ->method('authCallback');

        // Test again but both attempts should fail resulting in
        $integration->expects($this->exactly(2))
            ->method('makeRequest')
            ->willReturnOnConsecutiveCalls(
                [
                    [
                        'errorCode' => 'INVALID_SESSION_ID',
                        'message'   => $message,
                    ],
                ],
                [
                    ['success' => true],
                ]
            );

        $api = new SalesforceApi($integration);

        try {
            $api->request('/test');
        } catch (ApiErrorException) {
            $this->fail('ApiErrorException thrown');
        }
    }

    /**
     * @testdox Test that an exception is thrown for all other errors
     */
    public function testErrorDoesNotRetryRequest(): void
    {
        $integration = $this->createMock(SalesforceIntegration::class);
        $message     = 'Fatal error';

        $integration->expects($this->once())
            ->method('makeRequest')
            ->willReturn(
                [
                    [
                        'errorCode' => 'FATAL_ERROR',
                        'message'   => $message,
                    ],
                ]
            );

        $api = new SalesforceApi($integration);

        try {
            $api->request('/test');

            $this->fail('ApiErrorException not thrown');
        } catch (ApiErrorException $exception) {
            $this->assertEquals($message, $exception->getMessage());
        }
    }

    /**
     * @testdox Test that a backslash and a single quote are escaped for SF queries
     */
    public function testCompanyQueryIsEscapedCorrectly(): void
    {
        $integration = $this->getMockBuilder(SalesforceIntegration::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['mergeConfigToFeatureSettings', 'makeRequest', 'getQueryUrl', 'getIntegrationSettings', 'getFieldsForQuery', 'getApiUrl'])
            ->getMock();

        $integration->expects($this->once())
            ->method('mergeConfigToFeatureSettings')
            ->willReturn(
                [
                    'objects' => [
                        'company',
                    ],
                ]
            );

        $integration->expects($this->once())
            ->method('makeRequest')
            ->willReturnCallback(
                function ($url, $parameters = [], $method = 'GET', $settings = []): void {
                    $this->assertEquals(
                        [
                            'q' => 'select Id from Account where Name = \'Some\\\\thing E\\\'lse\' and BillingCountry =  \'Some\\\\Where E\\\'lse\' and BillingCity =  \'Some\\\\Where E\\\'lse\' and BillingState =  \'Some\\\\Where E\\\'lse\'',
                        ],
                        $parameters
                    );
                }
            );

        $api = new SalesforceApi($integration);

        $api->getCompany(
            [
                'company' => [
                    'BillingCountry' => 'Some\\Where E\'lse',
                    'BillingCity'    => 'Some\\Where E\'lse',
                    'BillingState'   => 'Some\\Where E\'lse',
                    'Name'           => 'Some\\thing E\'lse',
                ],
            ]
        );
    }

    /**
     * @testdox Test that a backslash and an html entity of single quote are escaped for SF queries
     *
     * @covers \MauticPlugin\MauticCrmBundle\Api\SalesforceApi::escapeQueryValue
     */
    public function testCompanyQueryWithHtmlEntitiesIsEscapedCorrectly(): void
    {
        $integration = $this->getMockBuilder(SalesforceIntegration::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['mergeConfigToFeatureSettings', 'makeRequest', 'getQueryUrl', 'getIntegrationSettings', 'getFieldsForQuery', 'getApiUrl'])
            ->getMock();

        $integration->expects($this->once())
            ->method('mergeConfigToFeatureSettings')
            ->willReturn(
                [
                    'objects' => [
                        'company',
                    ],
                ]
            );

        $integration->expects($this->once())
            ->method('makeRequest')
            ->willReturnCallback(
                function ($url, $parameters = [], $method = 'GET', $settings = []): void {
                    $this->assertEquals(
                        [
                            'q' => 'select Id from Account where Name = \'Some\\\\thing\\\' E\\\'lse\' and BillingCountry =  \'Some\\\\Where\\\' E\\\'lse\' and BillingCity =  \'Some\\\\Where\\\' E\\\'lse\' and BillingState =  \'Some\\\\Where\\\' E\\\'lse\'',
                        ],
                        $parameters
                    );
                }
            );

        $api = new SalesforceApi($integration);

        $api->getCompany(
            [
                'company' => [
                    'BillingCountry' => 'Some\\Where&#39; E\'lse',
                    'BillingCity'    => 'Some\\Where&#39; E\'lse',
                    'BillingState'   => 'Some\\Where&#39; E\'lse',
                    'Name'           => 'Some\\thing&#39; E\'lse',
                ],
            ]
        );
    }

    /**
     * @testdox Test that a backslash and a single quote are escaped for SF queries
     */
    public function testContactQueryIsEscapedCorrectly(): void
    {
        $integration = $this->getMockBuilder(SalesforceIntegration::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['mergeConfigToFeatureSettings', 'makeRequest', 'getQueryUrl', 'getIntegrationSettings', 'getFieldsForQuery', 'getApiUrl'])
            ->getMock();

        $integration->expects($this->once())
            ->method('mergeConfigToFeatureSettings')
            ->willReturn(
                [
                    'objects' => [
                        'Contact',
                    ],
                ]
            );

        $integration->expects($this->once())
            ->method('getFieldsForQuery')
            ->willReturn([]);

        $integration->expects($this->once())
            ->method('makeRequest')
            ->willReturnCallback(
                function ($url, $parameters = [], $method = 'GET', $settings = []): void {
                    $this->assertEquals(
                        [
                            'q' => 'select Id from Contact where email = \'con\\\\tact\\\'email@email.com\'',
                        ],
                        $parameters
                    );
                }
            );

        $integration->method('getFieldsForQuery')
            ->willReturn([]);

        $api = new SalesforceApi($integration);

        $api->getPerson([
            'Contact' => [
                'Email' => 'con\\tact\'email@email.com',
            ],
        ]);
    }

    /**
     * @testdox Test that a backslash and a single quote are escaped for SF queries
     */
    public function testLeadQueryIsEscapedCorrectly(): void
    {
        $integration = $this->getMockBuilder(SalesforceIntegration::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['mergeConfigToFeatureSettings', 'makeRequest', 'getQueryUrl', 'getIntegrationSettings', 'getFieldsForQuery', 'getApiUrl'])
            ->getMock();

        $integration->expects($this->once())
            ->method('mergeConfigToFeatureSettings')
            ->willReturn(
                [
                    'objects' => [
                        'Lead',
                    ],
                ]
            );

        $integration->expects($this->once())
            ->method('getFieldsForQuery')
            ->willReturn([]);

        $integration->expects($this->once())
            ->method('makeRequest')
            ->willReturnCallback(
                function ($url, $parameters = [], $method = 'GET', $settings = []): void {
                    $this->assertEquals(
                        [
                            'q' => 'select Id from Lead where email = \'con\\\\tact\\\'email@email.com\' and ConvertedContactId = NULL',
                        ],
                        $parameters
                    );
                }
            );

        $integration->method('getFieldsForQuery')
            ->willReturn([]);

        $api = new SalesforceApi($integration);

        $api->getPerson([
            'Lead' => [
                'Email' => 'con\\tact\'email@email.com',
            ],
        ]);
    }

    public function testHandleDeletesGracefullyWithHasOptedOutOfEmailAsMissingField(): void
    {
        /**
         * @phpstan-ignore-next-line
         */
        $cache = $this->createMock(CacheStorageHelper::class);

        $cache
            ->method('get')
            ->withAnyParameters()
            ->willReturn('2019-05-22 19:36:30');

        $integration = $this->getMockBuilder(SalesforceIntegration::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'mergeConfigToFeatureSettings',
                'makeRequest',
                'getQueryUrl',
                'getIntegrationSettings',
                'getFieldsForQuery',
                'getApiUrl',
                'getCache',
                'getTranslator',
                'upsertUnreadAdminsNotification',
            ])
            ->getMock();

        $integration
            ->expects($this->atLeastOnce())
            ->method('getCache')
            ->willReturn($cache);

        $integration->method('getFieldsForQuery')
            ->with('Lead')
            ->willReturn(['firstname', 'lastname', 'HasOptedOutOfEmail']);

        $translator = $this->createMock(TranslatorInterface::class);

        $integration->method('getTranslator')->willReturn($translator);

        $this->expectException(ApiErrorException::class);
        $integration->expects($this->atLeastOnce())
            ->method('makeRequest')
            ->willReturn(
                [
                    [
                        'errorCode' => 'FATAL_ERROR',
                        'message'   => "ERROR at Row1\nNo such column 'HasOptedOutOfEmail' on entity 'Lead'",
                    ],
                ]
            );

        $params['start']    = '2019-05-22 19:36:30';
        $params['end']      = '2030-05-22 19:36:30';

        $api = new SalesforceApi($integration);

        self::assertEquals('2019-05-22 19:36:30', $api->getOrganizationCreatedDate());

        $api->getLeads($params, 'Lead');
    }

    public function testHandleDeletesGracefully(): void
    {
        /**
         * @phpstan-ignore-next-line
         */
        $cache = $this->createMock(CacheStorageHelper::class);

        $cache
            ->method('get')
            ->withAnyParameters()
            ->willReturn('2019-05-22 19:36:30');

        $integration = $this->getMockBuilder(SalesforceIntegration::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'mergeConfigToFeatureSettings',
                'makeRequest',
                'getQueryUrl',
                'getIntegrationSettings',
                'getFieldsForQuery',
                'getApiUrl',
                'getCache',
                'getTranslator',
                'upsertUnreadAdminsNotification',
                'getEntityManager',
            ])
            ->getMock();

        $integration
            ->expects($this->atLeastOnce())
            ->method('getCache')
            ->willReturn($cache);

        $integration->method('getFieldsForQuery')
            ->with('Lead')
            ->willReturn(['firstname', 'lastname', 'extraField']);

        $integration->expects($this->never())->method('upsertUnreadAdminsNotification');

        $entityManager = $this
            ->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $entity = $this
            ->getMockBuilder(Integration::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getFeatureSettings', 'setFeatureSettings'])
            ->getMock();

        $integration->method('getEntityManager')->willReturn($entityManager);
        $integration->method('getIntegrationSettings')->willReturn($entity);
        $entity->method('getFeatureSettings')->willReturn(['leadFields' => ['extraField__Lead' => '']]);

        $this->expectException(ApiErrorException::class);
        $integration->expects($this->atLeastOnce())
            ->method('makeRequest')
            ->willReturn(
                [
                    [
                        'errorCode' => 'FATAL_ERROR',
                        'message'   => "ERROR at Row1\nNo such column 'extraField' on entity 'Lead'",
                    ],
                ]
            );

        $params['start']    = '2019-05-22 19:36:30';
        $params['end']      = '2030-05-22 19:36:30';

        $api = new SalesforceApi($integration);

        self::assertEquals('2019-05-22 19:36:30', $api->getOrganizationCreatedDate());

        $api->getLeads($params, 'Lead');
    }
}
