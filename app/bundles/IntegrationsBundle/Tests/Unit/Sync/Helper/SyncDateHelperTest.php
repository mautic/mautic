<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Unit\Sync\Helper;

use Mautic\IntegrationsBundle\Sync\Helper\SyncDateHelper;
use PHPUnit\Framework\TestCase;

final class SyncDateHelperTest extends TestCase
{
    /**
     * @var SyncDateHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private \PHPUnit\Framework\MockObject\MockObject $syncDateHelper;

    protected function setUp(): void
    {
        $this->syncDateHelper = $this->getMockBuilder(SyncDateHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getLastSyncDateForObject'])
            ->getMock();
    }

    public function testSpecifiedFromDateTimeIsReturned(): void
    {
        $syncFromDateTime = new \DateTimeImmutable('2018-10-08 00:00:00');

        $this->syncDateHelper->setSyncDateTimes($syncFromDateTime);

        $this->assertEquals($syncFromDateTime, $this->syncDateHelper->getSyncFromDateTime('Test', 'Object'));
    }

    public function testLastSyncDateForIntegrationSyncObjectIsReturned(): void
    {
        $objectLastSyncDate = new \DateTimeImmutable('2018-10-08 00:00:00');

        $this->syncDateHelper->method('getLastSyncDateForObject')
            ->willReturn($objectLastSyncDate);

        $this->assertEquals($objectLastSyncDate, $this->syncDateHelper->getSyncFromDateTime('Test', 'Object'));
    }

    public function testSyncToDateTimeIsReturnedIfSpecified(): void
    {
        $syncToDateTime = new \DateTimeImmutable('2018-10-08 00:00:00');

        $this->syncDateHelper->setSyncDateTimes(null, $syncToDateTime);

        $this->assertEquals($syncToDateTime, $this->syncDateHelper->getSyncToDateTime());
    }

    public function testSyncDateTimeIsReturnedForSyncToDateTimeIfNotSpecified(): void
    {
        $this->syncDateHelper->setSyncDateTimes();

        $this->assertInstanceOf(\DateTimeImmutable::class, $this->syncDateHelper->getSyncToDateTime());
    }

    public function testThatSetInternalSyncStartDateTimeMethodUsesSyncToDateValueIfItIsEarlier(): void
    {
        // Although $fiveSecondsBefore value is expected to be in UTC timezone let's use another timezone
        // to check how the method handles such cases.
        $fiveSecondsBefore = new \DateTime('-5 seconds', new \DateTimeZone('Etc/GMT-5'));
        $this->syncDateHelper->setSyncDateTimes(null, $fiveSecondsBefore);
        $this->syncDateHelper->setInternalSyncStartDateTime();
        $internalSyncStartDateTime = $this->syncDateHelper->getInternalSyncStartDateTime();
        $this->assertSame($fiveSecondsBefore->getTimestamp(), $internalSyncStartDateTime->getTimestamp());
    }

    public function testPastMutableAndImmutableSyncBoundsAreNormalizedToUtc(): void
    {
        foreach ([\DateTime::class, \DateTimeImmutable::class] as $dateClass) {
            $date = new $dateClass('2000-01-02 03:04:05', new \DateTimeZone('America/Sao_Paulo'));
            $helper = $this->getMockBuilder(SyncDateHelper::class)->disableOriginalConstructor()->onlyMethods(['getLastSyncDateForObject'])->getMock();
            $helper->setSyncDateTimes(null, $date);
            $helper->setInternalSyncStartDateTime();

            $result = $helper->getInternalSyncStartDateTime();
            $this->assertInstanceOf(\DateTimeInterface::class, $result);
            $this->assertSame($date->getTimestamp(), $result->getTimestamp());
            $this->assertSame('UTC', $result->getTimezone()->getName());
            $this->assertSame('America/Sao_Paulo', $date->getTimezone()->getName());
        }
    }

    public function testThatSetInternalSyncStartDateTimeMethodUsesNowIfItIsEarlier(): void
    {
        // Although $fiveSecondsAfter value is expected to be in UTC timezone let's use another timezone
        // to check how the method handles such cases.
        $fiveSecondsAfter = new \DateTime('+5 seconds', new \DateTimeZone('Etc/GMT+5'));
        $this->syncDateHelper->setSyncDateTimes(null, $fiveSecondsAfter);
        $this->syncDateHelper->setInternalSyncStartDateTime();
        $now                       = new \DateTime('now', new \DateTimeZone('UTC'));
        $internalSyncStartDateTime = $this->syncDateHelper->getInternalSyncStartDateTime();
        $difference                = $internalSyncStartDateTime->getTimestamp() - $now->getTimestamp();

        // Add a 1 second buffer in case there is some delay
        $this->assertTrue((1 >= $difference) && (-1 < $difference));
    }
}
