<?php

namespace Mautic\CampaignBundle\Tests\Executioner\ContactFinder\Limiter;

use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;
use Mautic\CampaignBundle\Executioner\Exception\NoContactsFoundException;

class ContactLimiterTest extends \PHPUnit\Framework\TestCase
{
    public function testGetters()
    {
        $limiter = new ContactLimiter(1, 2, 3, 4, [1, 2, 3]);

        $this->assertEquals(1, $limiter->getBatchLimit());
        $this->assertEquals(2, $limiter->getContactId());
        $this->assertEquals(3, $limiter->getMinContactId());
        $this->assertEquals(4, $limiter->getMaxContactId());
        $this->assertEquals([1, 2, 3], $limiter->getContactIdList());
    }

    public function testBatchMinContactIsReturned()
    {
        $limiter = new ContactLimiter(1, 2, 3, 10, [1, 2, 3]);

        $limiter->setBatchMinContactId(5);
        $this->assertEquals(5, $limiter->getMinContactId());
    }

    public function testNoContactsFoundExceptionThrownIfIdIsLessThanMin()
    {
        $this->expectException(NoContactsFoundException::class);

        $limiter = new ContactLimiter(1, 2, 3, 10, [1, 2, 3]);
        $limiter->setBatchMinContactId(1);
    }

    public function testNoContactsFoundExceptionThrownIfIdIsMoreThanMax()
    {
        $this->expectException(NoContactsFoundException::class);

        $limiter = new ContactLimiter(1, 2, 3, 10, [1, 2, 3]);
        $limiter->setBatchMinContactId(11);
    }

    public function testNoContactsFoundExceptionThrownIfIdIsTheSameAsLastBatch()
    {
        $this->expectException(NoContactsFoundException::class);

        $limiter = new ContactLimiter(1, 2, 3, 10, [1, 2, 3]);
        $limiter->setBatchMinContactId(5);
        $limiter->setBatchMinContactId(5);
    }

    public function testExceptionNotThrownIfIdEqualsMinSoThatItsIsIncluded()
    {
        $limiter = new ContactLimiter(1, 2, 3, 10, [1, 2, 3]);
        $this->assertSame($limiter, $limiter->setBatchMinContactId(3));
    }

    public function testExceptionNotThrownIfIdEqualsMaxSoThatItsIsIncluded()
    {
        $limiter = new ContactLimiter(1, 2, 3, 10, [1, 2, 3]);
        $this->assertSame($limiter, $limiter->setBatchMinContactId(10));
    }

    public function testExceptionThrownIfThreadIdLargerThanMaxThreads()
    {
        $this->expectException(\InvalidArgumentException::class);

        new ContactLimiter(1, null, null, null, [], 5, 3);
    }
}
