<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Entity;

use Mautic\EmailBundle\Entity\EmailReply;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Entity\StatOpenDetail;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StatTest extends TestCase
{
    /**
     * @param int $count How many openDetails to add to the entity
     */
    #[DataProvider('addOpenDetailsTestProvider')]
    public function testAddOpenDetails(int $count): void
    {
        $stat = new Stat();

        // Add as many openDetails entries as specified in $count
        for ($i = 0; $i < $count; ++$i) {
            $stat->addOpenDetails([
                'data' => sprintf('Open %d of %d', $i + 1, $count),
            ]);
        }

        // Assert that the openCount reflects the total number of openDetails
        $this->assertEquals($count, $stat->getOpenCount());

        // Assert that the number of entries stored in the openDetails array
        // is equal to the lower of the two values openCount and
        // Stat::MAX_OPEN_DETAILS
        $this->assertCount(
            min(Stat::MAX_OPEN_DETAILS, $stat->getOpenCount()),
            $stat->getOpenDetails()
        );
    }

    /**
     * Data provider for addOpenDetails.
     */
    /**
     * @return \Iterator<string, array{int}>
     */
    public static function addOpenDetailsTestProvider(): \Iterator
    {
        yield 'no openDetails' => [0];
        yield 'one openDetail' => [1];
        yield 'low number of openDetails' => [10];
        yield 'one away from threshold' => [Stat::MAX_OPEN_DETAILS - 1];
        yield 'exactly at threshold' => [Stat::MAX_OPEN_DETAILS];
        yield 'one past threshold' => [Stat::MAX_OPEN_DETAILS + 1];
        yield 'slightly above threshold' => [Stat::MAX_OPEN_DETAILS + 10];
        yield 'well beyond threshold' => [Stat::MAX_OPEN_DETAILS * 10];
    }

    public function testAddBounceDetailsRoundTripsInOrder(): void
    {
        $stat = new Stat();

        $stat->addBounceDetails(['datetime' => '2026-01-01 00:00:00', 'reason' => 'Mailbox full']);
        $stat->addBounceDetails(['datetime' => '2026-01-02 00:00:00', 'reason' => 'Unknown user']);

        $this->assertCount(2, $stat->getDataOpenDetails());
        $this->assertEquals(
            [
                ['datetime' => '2026-01-01 00:00:00', 'reason' => 'Mailbox full', StatOpenDetail::ROW_ID_KEY => null],
                ['datetime' => '2026-01-02 00:00:00', 'reason' => 'Unknown user', StatOpenDetail::ROW_ID_KEY => null],
            ],
            $stat->getOpenDetails()[StatOpenDetail::BOUNCES_KEY]
        );
    }

    public function testAddBounceDetailsDoesNotIncreaseOpenCount(): void
    {
        $stat = new Stat();

        $stat->addBounceDetails(['datetime' => '2026-01-01 00:00:00', 'reason' => 'Mailbox full']);

        $this->assertSame(0, $stat->getOpenCount());
    }

    public function testGetOpenDetailsMergesBounceAndOpenRows(): void
    {
        $stat = new Stat();
        $stat->addOpenDetails(['datetime' => '2026-01-01 00:00:00', 'useragent' => 'UA-open']);
        $stat->addBounceDetails(['datetime' => '2026-01-02 00:00:00', 'reason' => 'Mailbox full']);

        $this->assertEquals(
            [
                0                           => ['datetime' => '2026-01-01 00:00:00', 'useragent' => 'UA-open', StatOpenDetail::ROW_ID_KEY => null],
                StatOpenDetail::BOUNCES_KEY => [
                    ['datetime' => '2026-01-02 00:00:00', 'reason' => 'Mailbox full', StatOpenDetail::ROW_ID_KEY => null],
                ],
            ],
            $stat->getOpenDetails()
        );
    }

    public function testChildRowWithoutBouncesKeyReadsBackAsOpenDetail(): void
    {
        // A child row is read as an open detail whenever it has no 'bounces' key, regardless of
        // whether it was written by setOpenDetail() directly rather than through addOpenDetails().
        $stat  = new Stat();
        $entry = new StatOpenDetail();
        $entry->setStat($stat);
        $entry->setDateSent(new \DateTime());
        $entry->setOpenDetail(['datetime' => '2026-01-01 00:00:00', 'useragent' => 'UA-open']);
        $stat->getDataOpenDetails()->add($entry);

        $this->assertEquals(
            [['datetime' => '2026-01-01 00:00:00', 'useragent' => 'UA-open', StatOpenDetail::ROW_ID_KEY => null]],
            $stat->getOpenDetails()
        );
    }

    public function testAddOpenDetailsWithoutIncreasingOpenCountCapsOnRowCount(): void
    {
        $stat = new Stat();

        for ($i = 0; $i < Stat::MAX_OPEN_DETAILS + 5; ++$i) {
            $stat->addOpenDetails(['data' => $i], false);
        }

        $this->assertSame(0, $stat->getOpenCount());
        $this->assertCount(Stat::MAX_OPEN_DETAILS, $stat->getDataOpenDetails());
    }

    public function testSetOpenDetailsRoundTripPreservesContentWithoutAFlush(): void
    {
        $stat = new Stat();
        $stat->addOpenDetails(['datetime' => '2026-01-01 00:00:00', 'useragent' => 'UA-open']);
        $stat->addBounceDetails(['datetime' => '2026-01-02 00:00:00', 'reason' => 'Mailbox full']);

        $stat->setOpenDetails($stat->getOpenDetails());

        $this->assertCount(2, $stat->getDataOpenDetails());
        $this->assertEquals(
            [
                0                           => ['datetime' => '2026-01-01 00:00:00', 'useragent' => 'UA-open', StatOpenDetail::ROW_ID_KEY => null],
                StatOpenDetail::BOUNCES_KEY => [
                    ['datetime' => '2026-01-02 00:00:00', 'reason' => 'Mailbox full', StatOpenDetail::ROW_ID_KEY => null],
                ],
            ],
            $stat->getOpenDetails()
        );
    }

    public function testSetOpenDetailsReplacesEntriesAddedBeforeItWasCalled(): void
    {
        $stat = new Stat();
        $stat->addOpenDetails(['datetime' => '2026-01-01 00:00:00', 'useragent' => 'UA-open']);

        $stat->setOpenDetails([
            ['datetime' => '2026-02-01 00:00:00', 'useragent' => 'UA-replacement'],
        ]);

        $this->assertCount(1, $stat->getDataOpenDetails());
        $this->assertEquals(
            [['datetime' => '2026-02-01 00:00:00', 'useragent' => 'UA-replacement', StatOpenDetail::ROW_ID_KEY => null]],
            $stat->getOpenDetails()
        );
    }

    public function testChanges(): void
    {
        $stat = new Stat();
        $stat->setEmailAddress('john@doe.email');
        $stat->setIsFailed(true);
        $stat->setDateRead(new \DateTime());
        $stat->setDateSent(new \DateTime());
        $stat->setLastOpened(new \DateTime());
        $stat->setIsRead(false);
        $stat->setOpenCount(2);
        $stat->setRetryCount(3);
        $stat->setSource('campaign');
        $stat->setSourceId(123);
        $stat->addReply(new EmailReply($stat, '456'));

        $this->assertSame([null, 'john@doe.email'], $stat->getChanges()['emailAddress']);
        $this->assertSame([false, true], $stat->getChanges()['isFailed']);
        $this->assertSame([0, 2], $stat->getChanges()['openCount']);
        $this->assertSame([0, 3], $stat->getChanges()['retryCount']);
        $this->assertSame([null, 'campaign'], $stat->getChanges()['source']);
        $this->assertSame([null, 123], $stat->getChanges()['sourceId']);
        $this->assertSame([false, true], $stat->getChanges()['replyAdded']);
        $this->assertArrayNotHasKey('isRead', $stat->getChanges()); // Don't want to record changes from false to false.
        $this->assertNull($stat->getChanges()['dateRead'][0]);
        $this->assertInstanceOf(\DateTime::class, $stat->getChanges()['dateRead'][1]);
        $this->assertNull($stat->getChanges()['dateSent'][0]);
        $this->assertInstanceOf(\DateTime::class, $stat->getChanges()['dateSent'][1]);
        $this->assertNull($stat->getChanges()['lastOpened'][0]);
        $this->assertInstanceOf(\DateTime::class, $stat->getChanges()['lastOpened'][1]);

        $stat->upOpenCount();
        $stat->upRetryCount();
        $stat->setEmailAddress('john@doe.email');
        $stat->setDateRead(new \DateTime());
        $stat->setIsRead(true);
        $stat->setSource('campaign');
        $stat->setSourceId(321);
        $stat->addReply(new EmailReply($stat, '456'));

        $this->assertSame([null, 'john@doe.email'], $stat->getChanges()['emailAddress']);
        $this->assertSame([false, true], $stat->getChanges()['isFailed']);
        $this->assertSame([2, 3], $stat->getChanges()['openCount']);
        $this->assertSame([3, 4], $stat->getChanges()['retryCount']);
        $this->assertSame([null, 'campaign'], $stat->getChanges()['source']);
        $this->assertSame([123, 321], $stat->getChanges()['sourceId']);
        $this->assertSame([false, true], $stat->getChanges()['replyAdded']);
        $this->assertSame([false, true], $stat->getChanges()['isRead']);
        $this->assertInstanceOf(\DateTime::class, $stat->getChanges()['dateRead'][0]);
        $this->assertInstanceOf(\DateTime::class, $stat->getChanges()['dateRead'][1]);
        $this->assertNull($stat->getChanges()['dateSent'][0]);
        $this->assertInstanceOf(\DateTime::class, $stat->getChanges()['dateSent'][1]);
    }
}
