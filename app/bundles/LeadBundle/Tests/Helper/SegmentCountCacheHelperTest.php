<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Helper;

use Mautic\CacheBundle\Cache\CacheProviderInterface;
use Mautic\LeadBundle\Helper\SegmentCountCacheHelper;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\CacheItem;

class SegmentCountCacheHelperTest extends TestCase
{
    /**
     * @var CacheProviderInterface|MockObject
     */
    private MockObject $cacheProviderMock;

    private SegmentCountCacheHelper $segmentCountCacheHelper;

    /**
     * @var CacheItemInterface
     */
    private $cacheItem;

    protected function setUp(): void
    {
        $this->cacheProviderMock       = $this->createMock(CacheProviderInterface::class);
        $this->cacheItem               = $this->createMock(CacheItemInterface::class);
        $this->segmentCountCacheHelper = new SegmentCountCacheHelper($this->cacheProviderMock);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testGetSegmentContactCount(): void
    {
        $segmentId = 1;
        $this->cacheProviderMock
            ->method('getItem')
            ->with('segment.'.$segmentId.'.lead')
            ->willReturn($this->cacheItem);

        $this->cacheItem
            ->method('get')
            ->willReturn(1);

        $count = $this->segmentCountCacheHelper->getSegmentContactCount($segmentId);
        Assert::assertSame(1, $count);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testSetSegmentContactCount(): void
    {
        $segmentId = 1;
        $count     = 2;

        $this->cacheProviderMock
            ->method('getItem')
            ->with('segment.'.$segmentId.'.lead')
            ->willReturn($this->cacheItem);

        $this->cacheItem
            ->method('set')
            ->willReturn($count);

        $this->cacheProviderMock
            ->method('hasItem')
            ->with('segment.'.$segmentId.'.lead.recount')
            ->willReturn(false);

        $this->cacheProviderMock
            ->expects(self::never())
            ->method('deleteItem')
            ->with('segment.'.$segmentId.'.lead.recount');

        $this->segmentCountCacheHelper->setSegmentContactCount($segmentId, $count);
        Assert::isNull();
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testSetSegmentContactCountIfRecountExist(): void
    {
        $segmentId = 1;
        $count     = 2;

        $this->cacheProviderMock
            ->method('getItem')
            ->with('segment.'.$segmentId.'.lead')
            ->willReturn($this->cacheItem);

        $this->cacheItem
            ->method('set')
            ->willReturn($count);

        $this->cacheProviderMock
            ->expects(self::exactly(1))
            ->method('hasItem')
            ->with('segment.'.$segmentId.'.lead.recount')
            ->willReturn(true);

        $this->cacheProviderMock
            ->expects(self::exactly(1))
            ->method('deleteItem')
            ->with('segment.'.$segmentId.'.lead.recount');

        $this->segmentCountCacheHelper->setSegmentContactCount($segmentId, $count);
        Assert::isNull();
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testSetSegmentContactCountWithInvalidatedSegment(): void
    {
        $segmentId = 1;

        $this->cacheProviderMock
            ->method('getItem')
            ->with('segment.'.$segmentId.'.lead.recount')
            ->willReturn($this->cacheItem);

        $this->cacheItem
            ->expects(self::exactly(1))
            ->method('set')
            ->willReturn(true);

        $this->segmentCountCacheHelper->invalidateSegmentContactCount($segmentId);
        Assert::isNull();
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testDecrementSegmentContactCountHasNoCache(): void
    {
        $segmentId = 1;
        $this->cacheProviderMock
            ->expects(self::exactly(1))
            ->method('hasItem')
            ->with('segment.'.$segmentId.'.lead')
            ->willReturn(false);
        $this->segmentCountCacheHelper->decrementSegmentContactCount($segmentId);
        Assert::isNull();
    }

    public function testDeleteSegmentContactCountIfNotExist(): void
    {
        $segmentId = 1;
        $this->cacheStorageHelperMock
            ->expects(self::exactly(1))
            ->method('has')
            ->with('segment.'.$segmentId.'.lead')
            ->willReturn(false);
        $this->segmentCountCacheHelper->deleteSegmentContactCount($segmentId);
        Assert::isNull();
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testDeleteSegmentContactCountIfExist(): void
    {
        $segmentId = 1;
        $this->cacheStorageHelperMock
            ->expects(self::exactly(1))
            ->method('has')
            ->with('segment.'.$segmentId.'.lead')
            ->willReturn(true);

        $this->cacheStorageHelperMock
            ->expects(self::exactly(1))
            ->method('delete')
            ->with('segment.'.$segmentId.'.lead');

        $this->segmentCountCacheHelper->deleteSegmentContactCount($segmentId);
        Assert::isNull();
    }

    public function testDecrementSegmentContactCount(): void
    {
        $segmentId = 1;
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
            ->withConsecutive(['segment.'.$segmentId.'.lead'], ['segment.'.$segmentId.'.lead'], ['segment.'.$segmentId.'.lead.recount'])
            ->willReturn($this->cacheItem);

        $this->cacheItem
            ->method('get')
            ->willReturn('10');
        // Decrement count.
        $this->cacheItem
            ->expects(self::exactly(1))
            ->method('set');

        $this->segmentCountCacheHelper->decrementSegmentContactCount($segmentId);
        Assert::isNull();
    }

    public function testDecrementSegmentCountIsNotNegative(): void
    {
        $segmentId = 1;
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
            ->withConsecutive(['segment.'.$segmentId.'.lead'], ['segment.'.$segmentId.'.lead'], ['segment.'.$segmentId.'.lead.recount'])
            ->willReturn($this->cacheItem);
        // Edge case. Should not decrement below 0.
        $this->cacheItem
            ->expects(self::exactly(1))
            ->method('set');

        $this->segmentCountCacheHelper->decrementSegmentContactCount($segmentId);
        Assert::isNull();
    }
}
