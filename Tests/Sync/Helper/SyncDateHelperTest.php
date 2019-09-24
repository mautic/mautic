<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Tests\Sync\Helper;


use MauticPlugin\IntegrationsBundle\Sync\Helper\SyncDateHelper;

class SyncDateHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SyncDateHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $syncDateHelper;

    protected function setUp()
    {
        $this->syncDateHelper = $this->getMockBuilder(SyncDateHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['getLastSyncDateForObject'])
            ->getMock();
    }

    public function testSpecifiedFromDateTimeIsReturned()
    {
        $syncFromDateTime = new \DateTimeImmutable('2018-10-08 00:00:00');

        $this->syncDateHelper->setSyncDateTimes($syncFromDateTime);

        $this->assertEquals($syncFromDateTime, $this->syncDateHelper->getSyncFromDateTime('Test', 'Object'));
    }

    public function testLastSyncDateForIntegrationSyncObjectIsReturned()
    {
        $objectLastSyncDate = new \DateTimeImmutable('2018-10-08 00:00:00');

        $this->syncDateHelper->method('getLastSyncDateForObject')
            ->willReturn($objectLastSyncDate);

        $this->assertEquals($objectLastSyncDate, $this->syncDateHelper->getSyncFromDateTime('Test', 'Object'));
    }

    public function testSyncToDateTimeIsReturnedIfSpecified()
    {
        $syncToDateTime = new \DateTimeImmutable('2018-10-08 00:00:00');

        $this->syncDateHelper->setSyncDateTimes(null, $syncToDateTime);

        $this->assertEquals($syncToDateTime, $this->syncDateHelper->getSyncToDateTime());
    }


    public function testSyncDateTimeIsReturnedForSyncToDateTimeIfNotSpecified()
    {
        $this->syncDateHelper->setSyncDateTimes();

        $this->assertInstanceOf(\DateTimeImmutable::class, $this->syncDateHelper->getSyncToDateTime());
    }
}