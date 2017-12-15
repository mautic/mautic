<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\MauticCrmBundle\Tests\Api;

use Mautic\PluginBundle\Exception\ApiErrorException;
use MauticPlugin\MauticCrmBundle\Api\SalesforceApi;
use MauticPlugin\MauticCrmBundle\Integration\SalesforceIntegration;

class SalesforceApiTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @testdox Test that a locked record request is retried up to 3 times
     * @covers \MauticPlugin\MauticCrmBundle\Api\SalesforceApi::analyzeResponse()
     * @covers \MauticPlugin\MauticCrmBundle\Api\SalesforceApi::processError()
     * @covers \MauticPlugin\MauticCrmBundle\Api\SalesforceApi::checkIfLockedRequestShouldBeRetried()
     */
    public function testRecordLockedErrorIsRetriedThreeTimes()
    {
        $integration = $this->getMockBuilder(SalesforceIntegration::class)
            ->disableOriginalConstructor()
            ->getMock();

        $message = 'unable to obtain exclusive access to this record or 1 records: 70137000000Ugy3AAC';
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
     * @testdox Test that a locked record request is retried 2 times with 3rd being successful
     * @covers \MauticPlugin\MauticCrmBundle\Api\SalesforceApi::analyzeResponse()
     * @covers \MauticPlugin\MauticCrmBundle\Api\SalesforceApi::processError()
     * @covers \MauticPlugin\MauticCrmBundle\Api\SalesforceApi::checkIfLockedRequestShouldBeRetried()
     */
    public function testRecordLockedErrorIsRetriedTwoTimesWithThirdSuccess()
    {
        $integration = $this->getMockBuilder(SalesforceIntegration::class)
            ->disableOriginalConstructor()
            ->getMock();

        $message = 'unable to obtain exclusive access to this record or 1 records: 70137000000Ugy3AAC';
        $integration->expects($this->at(0))
            ->method('makeRequest')
            ->willReturn(
                [
                    [
                        'errorCode' => 'UNABLE_TO_LOCK_ROW',
                        'message'   => $message,
                    ],
                ]
            );
        $integration->expects($this->at(1))
            ->method('makeRequest')
            ->willReturn(
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
     * @covers \MauticPlugin\MauticCrmBundle\Api\SalesforceApi::analyzeResponse()
     * @covers \MauticPlugin\MauticCrmBundle\Api\SalesforceApi::processError()
     * @covers \MauticPlugin\MauticCrmBundle\Api\SalesforceApi::revalidateSession()
     */
    public function testSessionExpiredIsRefreshed()
    {
        $integration = $this->getMockBuilder(SalesforceIntegration::class)
            ->disableOriginalConstructor()
            ->getMock();

        $integration->expects($this->once())
            ->method('authCallback');

        $message = 'Session expired';
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
     * @testdox Test that an exception is thrown for all other errors
     * @covers \MauticPlugin\MauticCrmBundle\Api\SalesforceApi::analyzeResponse()
     * @covers \MauticPlugin\MauticCrmBundle\Api\SalesforceApi::processError()
     */
    public function testErrorDoesNotRetryRequest()
    {
        $integration = $this->getMockBuilder(SalesforceIntegration::class)
            ->disableOriginalConstructor()
            ->getMock();

        $message = 'Fatal error';
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
}
