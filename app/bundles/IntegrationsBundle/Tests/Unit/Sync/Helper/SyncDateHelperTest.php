<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Unit\Sync\Helper;

use Mautic\IntegrationsBundle\Sync\Helper\SyncDateHelper;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class SyncDateHelperTest extends TestCase
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

        Assert::assertEquals($syncFromDateTime, $this->syncDateHelper->getSyncFromDateTime('Test', 'Object'));
    }

    public function testLastSyncDateForIntegrationSyncObjectIsReturned(): void
    {
        $objectLastSyncDate = new \DateTimeImmutable('2018-10-08 00:00:00');

        $this->syncDateHelper->method('getLastSyncDateForObject')
            ->willReturn($objectLastSyncDate);

        Assert::assertEquals($objectLastSyncDate, $this->syncDateHelper->getSyncFromDateTime('Test', 'Object'));
    }

    public function testSyncToDateTimeIsReturnedIfSpecified(): void
    {
        $syncToDateTime = new \DateTimeImmutable('2018-10-08 00:00:00');

        $this->syncDateHelper->setSyncDateTimes(null, $syncToDateTime);

        Assert::assertEquals($syncToDateTime, $this->syncDateHelper->getSyncToDateTime());
    }

    public function testSyncDateTimeIsReturnedForSyncToDateTimeIfNotSpecified(): void
    {
        $this->syncDateHelper->setSyncDateTimes();

        Assert::assertInstanceOf(\DateTimeImmutable::class, $this->syncDateHelper->getSyncToDateTime());
    }

    public function testThatSetInternalSyncStartDateTimeMethodUsesSyncToDateValueIfItIsEarlier(): void
    {
        // Although $fiveSecondsBefore value is expected to be in UTC timezone let's use another timezone
        // to check how the method handles such cases.
        $fiveSecondsBefore = new \DateTime('-5 seconds', new \DateTimeZone('Etc/GMT-5'));
        $this->syncDateHelper->setSyncDateTimes(null, $fiveSecondsBefore);
        $this->syncDateHelper->setInternalSyncStartDateTime();
        $internalSyncStartDateTime = $this->syncDateHelper->getInternalSyncStartDateTime();
        Assert::assertSame($fiveSecondsBefore->getTimestamp(), $internalSyncStartDateTime->getTimestamp());
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
        Assert::assertTrue((1 >= $difference) && (-1 < $difference));
    }
}
