<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCrmBundle\Tests\Api;

use MauticPlugin\MauticCrmBundle\Api\ConnectwiseApi;
use MauticPlugin\MauticCrmBundle\Integration\ConnectwiseIntegration;
use MauticPlugin\MauticCrmBundle\Tests\Integration\DataGeneratorTrait;

class ConnectwiseApiTest extends \PHPUnit\Framework\TestCase
{
    use DataGeneratorTrait;

    /**
     * @testdox Tests that fetchAllRecords loops until all records are obtained
     * @covers  \MauticPlugin\MauticCrmBundle\Api\ConnectwiseApi::fetchAllRecords()
     *
     * @throws \Mautic\PluginBundle\Exception\ApiErrorException
     */
    public function testResultPagination()
    {
        $integration = $this->getMockBuilder(ConnectwiseIntegration::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['getRecords'])
            ->getMock();

        $page = 0;
        $integration->expects($this->exactly(3))
            ->method('makeRequest')
            ->willReturnCallback(
                function ($endpoint, $parameters) use (&$page) {
                    ++$page;

                    // Page should be incremented 3 times by fetchAllRecords method
                    $this->assertEquals(['page' => $page, 'pageSize' => ConnectwiseIntegration::PAGESIZE], $parameters);

                    return $this->generateData(3);
                }
            );

        $api = new ConnectwiseApi($integration);

        $records = $api->fetchAllRecords('test');

        $this->assertEquals($this->generatedRecords, $records);
    }
}
