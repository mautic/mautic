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
    
    public function testGetSegmentContactCount(): void
    {
        $segmentId = 1;
        $this->cacheStorageHelperMock
            ->expects(self::once())
            ->method('get')
            ->with('segment.'.$segmentId.'.lead')
            ->willReturn(1);

        $count = $this->segmentCountCacheHelper->getSegmentContactCount($segmentId);
        Assert::assertSame(1, $count);
    }

    public function testSetSegmentContactCount(): void
    {
        $segmentId = 1;
        $count     = 2;

        $this->cacheStorageHelperMock
            ->expects(self::once())
            ->method('set')
            ->with('segment.'.$segmentId.'.lead', $count);

        $this->cacheStorageHelperMock
            ->expects(self::once())
            ->method('has')
            ->with('segment.'.$segmentId.'.lead.recount')
            ->willReturn(false);

        $this->cacheStorageHelperMock
            ->expects(self::never())
            ->method('delete')
            ->with('segment.'.$segmentId.'.lead.recount');

        $this->segmentCountCacheHelper->setSegmentContactCount($segmentId, $count);
    }

    public function testSetSegmentContactCountIfRecountExist(): void
    {
        $segmentId = 1;
        $count     = 2;

        $this->cacheStorageHelperMock
            ->expects(self::once())
            ->method('set')
            ->with('segment.'.$segmentId.'.lead', $count);

        $this->cacheStorageHelperMock
            ->expects(self::once())
            ->method('has')
            ->with('segment.'.$segmentId.'.lead.recount')
            ->willReturn(true);

        $this->cacheStorageHelperMock
            ->expects(self::once())
            ->method('delete')
            ->with('segment.'.$segmentId.'.lead.recount');

        $this->segmentCountCacheHelper->setSegmentContactCount($segmentId, $count);
    }
    
    public function testInvalidateSegmentContactCount(): void
    {
        $segmentId = 1;

        $this->cacheStorageHelperMock
            ->expects(self::once())
            ->method('set')
            ->with('segment.'.$segmentId.'.lead.recount', true);

        $this->segmentCountCacheHelper->invalidateSegmentContactCount($segmentId);
    }
    
    public function testDeleteSegmentContactCountIfNotExist(): void
    {
        $segmentId = 1;
        $this->cacheStorageHelperMock
            ->expects(self::once())
            ->method('has')
            ->with('segment.'.$segmentId.'.lead')
            ->willReturn(false);
            
        $this->cacheStorageHelperMock
            ->expects(self::never())
            ->method('delete');
            
        $this->segmentCountCacheHelper->deleteSegmentContactCount($segmentId);
    }

    public function testDeleteSegmentContactCountIfExist(): void
    {
        $segmentId = 1;
        $this->cacheStorageHelperMock
            ->expects(self::once())
            ->method('has')
            ->with('segment.'.$segmentId.'.lead')
            ->willReturn(true);

        $this->cacheStorageHelperMock
            ->expects(self::once())
            ->method('delete')
            ->with('segment.'.$segmentId.'.lead');

        $this->segmentCountCacheHelper->deleteSegmentContactCount($segmentId);
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
