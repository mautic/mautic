<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Helper;

use Mautic\CacheBundle\Cache\CacheProviderInterface;
use Mautic\LeadBundle\Helper\SegmentCountCacheHelper;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\CacheItem;

class SegmentCountCacheHelperTest extends TestCase
{
    private MockObject&CacheProviderInterface $cacheProviderMock;

    private SegmentCountCacheHelper $segmentCountCacheHelper;

    protected function setUp(): void
    {
        $this->cacheProviderMock       = $this->createMock(CacheProviderInterface::class);
        $this->segmentCountCacheHelper = new SegmentCountCacheHelper($this->cacheProviderMock);
    }

    /**
     * Create a CacheItem instance using reflection since the constructor is private.
     */
    private function createCacheItem(string $key, mixed $value = null, bool $isHit = false): CacheItem
    {
        $item = (new \ReflectionClass(CacheItem::class))->newInstanceWithoutConstructor();

        $keyProperty = new \ReflectionProperty(CacheItem::class, 'key');
        $keyProperty->setValue($item, $key);

        $valueProperty = new \ReflectionProperty(CacheItem::class, 'value');
        $valueProperty->setValue($item, $value);

        $isHitProperty = new \ReflectionProperty(CacheItem::class, 'isHit');
        $isHitProperty->setValue($item, $isHit);

        return $item;
    }

    public function testGetSegmentContactCount(): void
    {
        $segmentId = 1;
        $cacheItem = $this->createCacheItem('segment.'.$segmentId.'.lead', 1, true);

        $this->cacheProviderMock
            ->method('getItem')
            ->with('segment.'.$segmentId.'.lead')
            ->willReturn($cacheItem);

        $count = $this->segmentCountCacheHelper->getSegmentContactCount($segmentId);
        Assert::assertSame(1, $count);
    }

    public function testSetSegmentContactCount(): void
    {
        $segmentId = 1;
        $count     = 2;
        $cacheItem = $this->createCacheItem('segment.'.$segmentId.'.lead');

        $this->cacheProviderMock
            ->method('getItem')
            ->with('segment.'.$segmentId.'.lead')
            ->willReturn($cacheItem);

        $this->cacheProviderMock
            ->method('hasItem')
            ->with('segment.'.$segmentId.'.lead.recount')
            ->willReturn(false);

        $this->cacheProviderMock
            ->expects(self::never())
            ->method('deleteItem')
            ->with('segment.'.$segmentId.'.lead.recount');

        $this->segmentCountCacheHelper->setSegmentContactCount($segmentId, $count);
    }

    public function testSetSegmentContactCountIfRecountExist(): void
    {
        $segmentId = 1;
        $count     = 2;
        $cacheItem = $this->createCacheItem('segment.'.$segmentId.'.lead');

        $this->cacheProviderMock
            ->method('getItem')
            ->with('segment.'.$segmentId.'.lead')
            ->willReturn($cacheItem);

        $this->cacheProviderMock
            ->expects(self::exactly(1))
            ->method('hasItem')
            ->with('segment.'.$segmentId.'.lead.recount')
            ->willReturn(true);

        $this->cacheProviderMock
            ->expects(self::exactly(1))
            ->method('deleteItem')
            ->with('segment.'.$segmentId.'.lead.recount')
            ->willReturn(true);

        $this->segmentCountCacheHelper->setSegmentContactCount($segmentId, $count);
    }

    public function testSetSegmentContactCountWithInvalidatedSegment(): void
    {
        $segmentId = 1;
        $cacheItem = $this->createCacheItem('segment.'.$segmentId.'.lead.recount');

        $this->cacheProviderMock
            ->expects(self::once())
            ->method('getItem')
            ->with('segment.'.$segmentId.'.lead.recount')
            ->willReturn($cacheItem);

        $this->cacheProviderMock
            ->expects(self::once())
            ->method('save')
            ->with($cacheItem);

        $this->segmentCountCacheHelper->invalidateSegmentContactCount($segmentId);
    }

    public function testDecrementSegmentContactCountHasNoCache(): void
    {
        $segmentId = 1;
        $this->cacheProviderMock
            ->expects(self::exactly(1))
            ->method('hasItem')
            ->with('segment.'.$segmentId.'.lead')
            ->willReturn(false);
        $this->segmentCountCacheHelper->decrementSegmentContactCount($segmentId);
    }

    public function testDeleteSegmentContactCountIfNotExist(): void
    {
        $segmentId = 1;
        $this->cacheProviderMock
            ->expects(self::exactly(1))
            ->method('hasItem')
            ->with('segment.'.$segmentId.'.lead')
            ->willReturn(false);
        $this->segmentCountCacheHelper->deleteSegmentContactCount($segmentId);
    }

    public function testDeleteSegmentContactCountIfExist(): void
    {
        $segmentId = 1;
        $this->cacheProviderMock
            ->expects(self::exactly(1))
            ->method('hasItem')
            ->with('segment.'.$segmentId.'.lead')
            ->willReturn(true);

        $this->cacheProviderMock
            ->expects(self::exactly(1))
            ->method('deleteItem')
            ->with('segment.'.$segmentId.'.lead')
            ->willReturn(true);

        $this->segmentCountCacheHelper->deleteSegmentContactCount($segmentId);
    }

    public function testDecrementSegmentContactCount(): void
    {
        $segmentId = 1;
        $cacheItem = $this->createCacheItem('segment.'.$segmentId.'.lead', 5, true);

        $this->cacheProviderMock
            ->method('hasItem')
            ->willReturnCallback(function ($key) use ($segmentId) {
                if ($key === 'segment.'.$segmentId.'.lead') {
                    return true;
                }
                if ($key === 'segment.'.$segmentId.'.lead.recount') {
                    return false;
                }

                return false;
            });

        $this->cacheProviderMock
            ->method('getItem')
            ->willReturnCallback(function ($key) use ($segmentId, $cacheItem) {
                if ($key === 'segment.'.$segmentId.'.lead') {
                    return $cacheItem;
                }

                return null;
            });

        $this->cacheProviderMock
            ->expects(self::once())
            ->method('save')
            ->with($cacheItem);

        $this->segmentCountCacheHelper->decrementSegmentContactCount($segmentId);

        // Verify the count was decremented from 5 to 4
        Assert::assertSame(4, $cacheItem->get());
    }

    public function testDecrementSegmentCountIsNotNegative(): void
    {
        $segmentId = 1;
        $cacheItem = $this->createCacheItem('segment.'.$segmentId.'.lead', 0, true);

        $this->cacheProviderMock
            ->expects(self::exactly(2))
            ->method('hasItem')
            ->willReturnCallback(function ($key) use ($segmentId) {
                if ($key === 'segment.'.$segmentId.'.lead') {
                    return true;
                }
                if ($key === 'segment.'.$segmentId.'.lead.recount') {
                    return false;
                }

                return false;
            });
        $this->cacheProviderMock
            ->method('getItem')
            ->willReturnCallback(function ($key) use ($segmentId, $cacheItem) {
                if (in_array($key, ['segment.'.$segmentId.'.lead', 'segment.'.$segmentId.'.lead.recount'])) {
                    return $cacheItem;
                }

                return null;
            });

        // Edge case. Should not decrement below 0.
        $this->segmentCountCacheHelper->decrementSegmentContactCount($segmentId);

        // Assert that the cache item value is still 0 (not negative)
        Assert::assertSame(0, $cacheItem->get());
    }
}
