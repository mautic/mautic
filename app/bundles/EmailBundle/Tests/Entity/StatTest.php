<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Entity;

use Mautic\EmailBundle\Entity\EmailReply;
use Mautic\EmailBundle\Entity\Stat;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class StatTest extends TestCase
{
    /**
     * @param int $count How many openDetails to add to the entity
     *
     * @dataProvider addOpenDetailsTestProvider
     */
    public function testAddOpenDetails(int $count): void
    {
        $stat = new Stat();

        // Add as many openDetails entries as specified in $count
        for ($i = 0; $i < $count; ++$i) {
            $stat->addOpenDetails(sprintf('Open %d of %d', $i + 1, $count));
        }

        // Assert that the openCount reflects the total number of openDetails
        $this->assertEquals($count, $stat->getOpenCount());

        // Assert that the number of entries stored in the openDetails array
        // is equal to the lower of the two values openCount and
        // Stat::MAX_OPEN_DETAILS
        $this->assertEquals(
            min(Stat::MAX_OPEN_DETAILS, $stat->getOpenCount()),
            count($stat->getOpenDetails())
        );
    }

    /**
     * Data provider for addOpenDetails.
     */
    public static function addOpenDetailsTestProvider(): array
    {
        return [
            'no openDetails'            => [0],
            'one openDetail'            => [1],
            'low number of openDetails' => [10],
            'one away from threshold'   => [Stat::MAX_OPEN_DETAILS - 1],
            'exactly at threshold'      => [Stat::MAX_OPEN_DETAILS],
            'one past threshold'        => [Stat::MAX_OPEN_DETAILS + 1],
            'slightly above threshold'  => [Stat::MAX_OPEN_DETAILS + 10],
            'well beyond threshold'     => [Stat::MAX_OPEN_DETAILS * 10],
        ];
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

        Assert::assertSame([null, 'john@doe.email'], $stat->getChanges()['emailAddress']);
        Assert::assertSame([false, true], $stat->getChanges()['isFailed']);
        Assert::assertSame([0, 2], $stat->getChanges()['openCount']);
        Assert::assertSame([0, 3], $stat->getChanges()['retryCount']);
        Assert::assertSame([null, 'campaign'], $stat->getChanges()['source']);
        Assert::assertSame([null, 123], $stat->getChanges()['sourceId']);
        Assert::assertSame([false, true], $stat->getChanges()['replyAdded']);
        Assert::assertArrayNotHasKey('isRead', $stat->getChanges()); // Don't want to record changes from false to false.
        Assert::assertNull($stat->getChanges()['dateRead'][0]);
        Assert::assertInstanceOf(\DateTime::class, $stat->getChanges()['dateRead'][1]);
        Assert::assertNull($stat->getChanges()['dateSent'][0]);
        Assert::assertInstanceOf(\DateTime::class, $stat->getChanges()['dateSent'][1]);
        Assert::assertNull($stat->getChanges()['lastOpened'][0]);
        Assert::assertInstanceOf(\DateTime::class, $stat->getChanges()['lastOpened'][1]);

        $stat->upOpenCount();
        $stat->upRetryCount();
        $stat->setEmailAddress('john@doe.email');
        $stat->setDateRead(new \DateTime());
        $stat->setIsRead(true);
        $stat->setSource('campaign');
        $stat->setSourceId(321);
        $stat->addReply(new EmailReply($stat, '456'));

        Assert::assertSame([null, 'john@doe.email'], $stat->getChanges()['emailAddress']);
        Assert::assertSame([false, true], $stat->getChanges()['isFailed']);
        Assert::assertSame([2, 3], $stat->getChanges()['openCount']);
        Assert::assertSame([3, 4], $stat->getChanges()['retryCount']);
        Assert::assertSame([null, 'campaign'], $stat->getChanges()['source']);
        Assert::assertSame([123, 321], $stat->getChanges()['sourceId']);
        Assert::assertSame([false, true], $stat->getChanges()['replyAdded']);
        Assert::assertSame([false, true], $stat->getChanges()['isRead']);
        Assert::assertInstanceOf(\DateTime::class, $stat->getChanges()['dateRead'][0]);
        Assert::assertInstanceOf(\DateTime::class, $stat->getChanges()['dateRead'][1]);
        Assert::assertNull($stat->getChanges()['dateSent'][0]);
        Assert::assertInstanceOf(\DateTime::class, $stat->getChanges()['dateSent'][1]);
    }
}
