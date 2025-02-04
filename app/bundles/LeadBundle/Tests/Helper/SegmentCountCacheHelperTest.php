<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Helper;

use Mautic\CoreBundle\Helper\CacheStorageHelper;
use Mautic\LeadBundle\Helper\SegmentCountCacheHelper;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SegmentCountCacheHelperTest extends TestCase
{
    /**
     * @var CacheStorageHelper|MockObject
     */
    private MockObject $cacheStorageHelperMock;

    private SegmentCountCacheHelper $segmentCountCacheHelper;

    protected function setUp(): void
    {
        $this->cacheStorageHelperMock  = $this->createMock(CacheStorageHelper::class);
        $this->segmentCountCacheHelper = new SegmentCountCacheHelper($this->cacheStorageHelperMock);
    }

    public function testDecrementSegmentContactCountHasNoCache(): void
    {
        $segmentId = 1;
        $this->cacheStorageHelperMock
            ->expects(self::once())
            ->method('has')
            ->with('segment.'.$segmentId.'.lead')
            ->willReturn(false);
        $this->segmentCountCacheHelper->decrementSegmentContactCount($segmentId);
        Assert::isNull();
    }

    public function testDecrementSegmentContactCount(): void
    {
        $segmentId = 1;
        $this->cacheStorageHelperMock
            ->expects(self::once())
            ->method('has')
            ->with('segment.'.$segmentId.'.lead')
            ->willReturn(true);
        $this->cacheStorageHelperMock
            ->expects(self::once())
            ->method('get')
            ->with('segment.'.$segmentId.'.lead')
            ->willReturn('10');
        // Decrement count.
        $this->cacheStorageHelperMock
            ->expects(self::once())
            ->method('set')
            ->with('segment.'.$segmentId.'.lead', 9);
        $this->segmentCountCacheHelper->decrementSegmentContactCount($segmentId);
        Assert::isNull();
    }

    public function testDecrementSegmentCountIsNotNegative(): void
    {
        $segmentId = 1;
        $this->cacheStorageHelperMock
            ->expects(self::once())
            ->method('has')
            ->with('segment.'.$segmentId.'.lead')
            ->willReturn(true);
        $this->cacheStorageHelperMock
            ->expects(self::once())
            ->method('get')
            ->with('segment.'.$segmentId.'.lead')
            ->willReturn('0');
        // Edge case. Should not decrement below 0.
        $this->cacheStorageHelperMock
            ->expects(self::once())
            ->method('set')
            ->with('segment.'.$segmentId.'.lead', 0);
        $this->segmentCountCacheHelper->decrementSegmentContactCount($segmentId);
        Assert::isNull();
    }
}
