<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCrmBundle\Tests\Integration;

use MauticPlugin\MauticCrmBundle\Api\ConnectwiseApi;
use MauticPlugin\MauticCrmBundle\Integration\ConnectwiseIntegration;

class ConnectwiseIntegrationTest extends \PHPUnit_Framework_TestCase
{
    use DataGeneratorTrait;

    /**
     * @testdox Test that all records are fetched till last page of results are consumed
     * @covers  \MauticPlugin\MauticCrmBundle\Integration\ConnectwiseIntegration::getRecords()
     */
    public function testMultiplePagesOfRecordsAreFetched()
    {
        $apiHelper = $this->getMockBuilder(ConnectwiseApi::class)
            ->disableOriginalConstructor()
            ->getMock();

        $apiHelper->expects($this->exactly(2))
            ->method('getContacts')
            ->willReturnCallback(
                function () {
                    return $this->generateData(2);
                }
            );

        $integration = $this->getMockBuilder(ConnectwiseIntegration::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['getRecords'])
            ->getMock();

        $integration->expects($this->once())
            ->method('isAuthorized')
            ->willReturn(true);

        $integration
            ->method('getApiHelper')
            ->willReturn($apiHelper);

        $integration->getRecords([], 'Contact');
    }
}
