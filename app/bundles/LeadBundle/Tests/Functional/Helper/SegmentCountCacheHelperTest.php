<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional\Helper;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Helper\SegmentCountCacheHelper;
use PHPUnit\Framework\Attributes\Group;

#[Group('non-parallel')]
final class SegmentCountCacheHelperTest extends MauticMysqlTestCase
{
    private const int SEGMENT_ID = 1;

    private SegmentCountCacheHelper $segmentCountCacheHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->segmentCountCacheHelper = $this->getContainer()->get(SegmentCountCacheHelper::class);

        // Delete the cache before each test starts.
        $this->segmentCountCacheHelper->setSegmentContactCount(self::SEGMENT_ID, 20);
        $this->segmentCountCacheHelper->deleteSegmentContactCount(self::SEGMENT_ID);
    }

    public function testWorkflowForSegmentCount(): void
    {
        $this->assertSame(0, $this->segmentCountCacheHelper->getSegmentContactCount(self::SEGMENT_ID));
        $this->assertFalse($this->segmentCountCacheHelper->hasSegmentContactCount(self::SEGMENT_ID));

        $this->segmentCountCacheHelper->setSegmentContactCount(self::SEGMENT_ID, 100);
        $this->assertSame(100, $this->segmentCountCacheHelper->getSegmentContactCount(self::SEGMENT_ID));
        $this->assertTrue($this->segmentCountCacheHelper->hasSegmentContactCount(self::SEGMENT_ID));

        $this->segmentCountCacheHelper->incrementSegmentContactCount(self::SEGMENT_ID);
        $this->assertSame(101, $this->segmentCountCacheHelper->getSegmentContactCount(self::SEGMENT_ID));

        $this->segmentCountCacheHelper->decrementSegmentContactCount(self::SEGMENT_ID);
        $this->assertSame(100, $this->segmentCountCacheHelper->getSegmentContactCount(self::SEGMENT_ID));

        $this->segmentCountCacheHelper->deleteSegmentContactCount(self::SEGMENT_ID);
        $this->assertSame(0, $this->segmentCountCacheHelper->getSegmentContactCount(self::SEGMENT_ID));
        $this->assertFalse($this->segmentCountCacheHelper->hasSegmentContactCount(self::SEGMENT_ID));
    }

    public function testDecrementCannotGoNegative(): void
    {
        $this->assertSame(0, $this->segmentCountCacheHelper->getSegmentContactCount(self::SEGMENT_ID));

        // Ensure we cannot decrement bellow zero.
        $this->segmentCountCacheHelper->decrementSegmentContactCount(self::SEGMENT_ID);
        $this->assertSame(0, $this->segmentCountCacheHelper->getSegmentContactCount(self::SEGMENT_ID));
    }

    public function testWorkflowForSegmentReount(): void
    {
        $this->assertFalse($this->segmentCountCacheHelper->hasSegmentIdForReCount(self::SEGMENT_ID));

        $this->segmentCountCacheHelper->invalidateSegmentContactCount(self::SEGMENT_ID);

        $this->assertTrue($this->segmentCountCacheHelper->hasSegmentIdForReCount(self::SEGMENT_ID));

        // Setting the count will delete the invalidation.
        $this->segmentCountCacheHelper->setSegmentContactCount(self::SEGMENT_ID, 100);

        $this->assertFalse($this->segmentCountCacheHelper->hasSegmentIdForReCount(self::SEGMENT_ID));

        // Cleanup.
        $this->segmentCountCacheHelper->deleteSegmentContactCount(self::SEGMENT_ID);
    }
}
