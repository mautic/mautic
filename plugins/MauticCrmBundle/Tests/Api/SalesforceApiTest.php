<?php

declare(strict_types=1);

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCrmBundle\Tests\Api;

use Mautic\PluginBundle\Exception\ApiErrorException;
use MauticPlugin\MauticCrmBundle\Api\SalesforceApi;
use MauticPlugin\MauticCrmBundle\Integration\SalesforceIntegration;

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
        } catch (ApiErrorException $exception) {
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
        } catch (ApiErrorException $exception) {
            $this->fail('ApiErrorException should not have been thrown');
        }
    }

    /**
     * @testdox Test that a session expired should attempt a refresh before failing
     */
    public function testSessionExpiredIsRefreshed(): void
    {
        $integration = $this->createMock(SalesforceIntegration::class);
        $message     = 'Session expired';

        $integration->expects($this->exactly(2))
            ->method('authCallback');

        $integration->expects($this->exactly(2))
            ->method('makeRequest')
            ->willReturn(
                [
                    [
                        'errorCode' => 'INVALID_SESSION_ID',
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
        } catch (ApiErrorException $exception) {
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

        $integration->expects($this->exactly(1))
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
            ->setMethodsExcept(['cleanPushData'])
            ->getMock();

        $integration->expects($this->exactly(1))
            ->method('mergeConfigToFeatureSettings')
            ->willReturn(
                [
                    'objects' => [
                        'company',
                    ],
                ]
            );

        $integration->expects($this->exactly(1))
            ->method('makeRequest')
            ->willReturnCallback(
                function ($url, $parameters = [], $method = 'GET', $settings = []) {
                    $this->assertEquals(
                        $parameters,
                        [
                            'q' => 'select Id from Account where Name = \'Some\\\\thing E\\\'lse\' and BillingCountry =  \'Some\\\\Where E\\\'lse\' and BillingCity =  \'Some\\\\Where E\\\'lse\' and BillingState =  \'Some\\\\Where E\\\'lse\'',
                        ]
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
     * @testdox Test that a backslash and a single quote are escaped for SF queries
     */
    public function testContactQueryIsEscapedCorrectly(): void
    {
        $integration = $this->getMockBuilder(SalesforceIntegration::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['cleanPushData'])
            ->getMock();

        $integration->expects($this->exactly(1))
            ->method('mergeConfigToFeatureSettings')
            ->willReturn(
                [
                    'objects' => [
                        'Contact',
                    ],
                ]
            );

        $integration->expects($this->exactly(1))
            ->method('makeRequest')
            ->willReturnCallback(
                function ($url, $parameters = [], $method = 'GET', $settings = []) {
                    $this->assertEquals(
                        $parameters,
                        [
                            'q' => 'select Id from Contact where email = \'con\\\\tact\\\'email@email.com\'',
                        ]
                    );
                }
            );

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
            ->setMethodsExcept(['cleanPushData'])
            ->getMock();

        $integration->expects($this->exactly(1))
            ->method('mergeConfigToFeatureSettings')
            ->willReturn(
                [
                    'objects' => [
                        'Lead',
                    ],
                ]
            );

        $integration->expects($this->exactly(1))
            ->method('makeRequest')
            ->willReturnCallback(
                function ($url, $parameters = [], $method = 'GET', $settings = []) {
                    $this->assertEquals(
                        $parameters,
                        [
                            'q' => 'select Id from Lead where email = \'con\\\\tact\\\'email@email.com\' and ConvertedContactId = NULL',
                        ]
                    );
                }
            );

        $api = new SalesforceApi($integration);

        $api->getPerson([
            'Lead' => [
                'Email' => 'con\\tact\'email@email.com',
            ],
        ]);
    }
}
