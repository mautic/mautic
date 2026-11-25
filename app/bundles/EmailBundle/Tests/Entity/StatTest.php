<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Entity;

use Doctrine\Common\Collections\Collection;
use Mautic\EmailBundle\Entity\EmailReply;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Entity\StatData;
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
            $stat->addOpenDetails(sprintf('Open %d of %d', $i + 1, $count));
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

    public function testTokensAreStoredInTheDataRowDatedWithTheSendDate(): void
    {
        $dateSent = new \DateTime('2026-01-02 03:04:05');
        $stat     = new Stat();
        $stat->setTokens(['{contactfield=firstname}' => 'John']);
        $stat->setDateSent($dateSent);

        $this->assertSame(['{contactfield=firstname}' => 'John'], $stat->getTokens());
        $this->assertSame(['{contactfield=firstname}' => 'John'], $this->getDataRow($stat)->getTokens());
        $this->assertEquals($dateSent, $this->getDataRow($stat)->getDateSent());
        $this->assertNull($this->getLegacyTokens($stat));
    }

    public function testTokensInTheLegacyColumnAreReturned(): void
    {
        $stat = new Stat();
        $this->setLegacyTokens($stat, ['{legacy}' => 'old']);

        $this->assertSame(['{legacy}' => 'old'], $stat->getTokens());
    }

    public function testSettingTokensReplacesTokensInTheLegacyColumn(): void
    {
        $stat = new Stat();
        $this->setLegacyTokens($stat, ['{legacy}' => 'old']);
        $stat->setTokens(['{new}' => 'value']);

        $this->assertSame(['{new}' => 'value'], $stat->getTokens());
    }

    public function testStoredTokensAreReportedWhileEitherStorageHoldsThem(): void
    {
        $legacy = new Stat();
        $this->setLegacyTokens($legacy, []);
        $this->assertTrue($legacy->hasStoredTokens());

        $stored = new Stat();
        $stored->setTokens([]);
        $this->assertTrue($stored->hasStoredTokens());

        $removed = new Stat();
        $this->setLegacyTokens($removed, null);
        $this->assertFalse($removed->hasStoredTokens());
        $this->assertSame([], $removed->getTokens());
    }

    private function getDataRow(Stat $stat): StatData
    {
        $data = new \ReflectionProperty(Stat::class, 'dataCollection');
        $rows = $data->getValue($stat);
        $this->assertInstanceOf(Collection::class, $rows);
        $this->assertCount(1, $rows);
        $row = $rows->first();
        $this->assertInstanceOf(StatData::class, $row);

        return $row;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getLegacyTokens(Stat $stat): ?array
    {
        return new \ReflectionProperty(Stat::class, 'tokens')->getValue($stat);
    }

    /**
     * @param array<string, mixed>|null $tokens
     */
    private function setLegacyTokens(Stat $stat, ?array $tokens): void
    {
        new \ReflectionProperty(Stat::class, 'tokens')->setValue($stat, $tokens);
    }
}
