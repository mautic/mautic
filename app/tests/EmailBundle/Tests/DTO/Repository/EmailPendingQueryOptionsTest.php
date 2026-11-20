<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\DTO\Repository;

use Mautic\EmailBundle\DTO\Repository\EmailPendingQueryOptions;
use PHPUnit\Framework\TestCase;

final class EmailPendingQueryOptionsTest extends TestCase
{
    private EmailPendingQueryOptions $emailPendingQueryOptions;

    protected function setUp(): void
    {
        $this->emailPendingQueryOptions = new EmailPendingQueryOptions(4);
    }

    public function testEmailId(): void
    {
        $this->assertSame(4, $this->emailPendingQueryOptions->getEmailId());
    }

    public function testVariantIds(): void
    {
        $variantIds = [2, 4];
        $copy       = $this->emailPendingQueryOptions->setVariantIds($variantIds);

        $this->assertNotSame($copy, $this->emailPendingQueryOptions);
        $this->assertNull($this->emailPendingQueryOptions->getVariantIds());
        $this->assertSame($variantIds, $copy->getVariantIds());
    }

    public function testListIds(): void
    {
        $listIds = [7, 9];
        $copy    = $this->emailPendingQueryOptions->setListIds($listIds);

        $this->assertNotSame($copy, $this->emailPendingQueryOptions);
        $this->assertNull($this->emailPendingQueryOptions->getListIds());
        $this->assertSame($listIds, $copy->getListIds());
    }

    public function testCountOnly(): void
    {
        $copy = $this->emailPendingQueryOptions->setCountOnly(true);

        $this->assertNotSame($copy, $this->emailPendingQueryOptions);
        $this->assertFalse($this->emailPendingQueryOptions->isCountOnly());
        $this->assertTrue($copy->isCountOnly());
    }

    public function testLimit(): void
    {
        $limit = 50;
        $copy  = $this->emailPendingQueryOptions->setLimit($limit);

        $this->assertNotSame($copy, $this->emailPendingQueryOptions);
        $this->assertNull($this->emailPendingQueryOptions->getLimit());
        $this->assertSame($limit, $copy->getLimit());
    }

    public function testMinContactId(): void
    {
        $minContactId = 50;
        $copy         = $this->emailPendingQueryOptions->setMinContactId($minContactId);

        $this->assertNotSame($copy, $this->emailPendingQueryOptions);
        $this->assertNull($this->emailPendingQueryOptions->getMinContactId());
        $this->assertSame($minContactId, $copy->getMinContactId());
    }

    public function testMaxContactId(): void
    {
        $maxContactId = 100;
        $copy         = $this->emailPendingQueryOptions->setMaxContactId($maxContactId);

        $this->assertNotSame($copy, $this->emailPendingQueryOptions);
        $this->assertNull($this->emailPendingQueryOptions->getMaxContactId());
        $this->assertSame($maxContactId, $copy->getMaxContactId());
    }

    public function testCountWithMaxMin(): void
    {
        $copy = $this->emailPendingQueryOptions->setCountWithMaxMin(true);

        $this->assertNotSame($copy, $this->emailPendingQueryOptions);
        $this->assertFalse($this->emailPendingQueryOptions->isCountWithMaxMin());
        $this->assertTrue($copy->isCountWithMaxMin());
    }

    public function testMaxDate(): void
    {
        $maxDate = new \DateTime();
        $copy    = $this->emailPendingQueryOptions->setMaxDate($maxDate);

        $this->assertNotSame($copy, $this->emailPendingQueryOptions);
        $this->assertNotInstanceOf(\DateTimeInterface::class, $this->emailPendingQueryOptions->getMaxDate());
        $this->assertSame($maxDate, $copy->getMaxDate());
    }

    public function testMaxThreads(): void
    {
        $maxThreads = 10;
        $copy         = $this->emailPendingQueryOptions->setMaxThreads($maxThreads);

        $this->assertNotSame($copy, $this->emailPendingQueryOptions);
        $this->assertNull($this->emailPendingQueryOptions->getMaxThreads());
        $this->assertSame($maxThreads, $copy->getMaxThreads());
    }

    public function testThreadId(): void
    {
        $threadId = 21;
        $copy     = $this->emailPendingQueryOptions->setThreadId($threadId);

        $this->assertNotSame($copy, $this->emailPendingQueryOptions);
        $this->assertNull($this->emailPendingQueryOptions->getThreadId());
        $this->assertSame($threadId, $copy->getThreadId());
    }

    public function testSendStopDate(): void
    {
        $sendStopDate = new \DateTime();
        $copy    = $this->emailPendingQueryOptions->setSendStopDate($sendStopDate);

        $this->assertNotSame($copy, $this->emailPendingQueryOptions);
        $this->assertNotInstanceOf(\DateTimeInterface::class, $this->emailPendingQueryOptions->getSendStopDate());
        $this->assertSame($sendStopDate, $copy->getSendStopDate());
    }

    public function testSegmentMembershipViaJoin(): void
    {
        $copy = $this->emailPendingQueryOptions->setSegmentMembershipViaJoin(true);

        $this->assertNotSame($copy, $this->emailPendingQueryOptions);
        $this->assertFalse($this->emailPendingQueryOptions->isSegmentMembershipViaJoin());
        $this->assertTrue($copy->isSegmentMembershipViaJoin());
    }
}
