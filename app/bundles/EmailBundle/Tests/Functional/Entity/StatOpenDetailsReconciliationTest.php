<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Functional\Entity;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Entity\StatOpenDetail;
use PHPUnit\Framework\Assert;

final class StatOpenDetailsReconciliationTest extends MauticMysqlTestCase
{
    private const DATE_ONE = '2026-01-01 00:00:00';

    private const DATE_TWO = '2026-01-02 00:00:00';

    private const EDITED_REASON = 'Edited reason';

    public function testSetOpenDetailsReconcilesByRowIdAfterAFlush(): void
    {
        $stat = new Stat();
        $stat->setEmailAddress('john@doe.cz');
        $stat->setDateSent(new \DateTime());

        $stat->addOpenDetails(['datetime' => self::DATE_ONE, 'useragent' => 'UA-one']);
        $stat->addOpenDetails(['datetime' => self::DATE_TWO, 'useragent' => 'UA-two']);
        $stat->addBounceDetails(['datetime' => '2026-01-03 00:00:00', 'reason' => 'Mailbox full']);

        $this->em->persist($stat);
        $this->em->flush();

        $statId = $stat->getId();
        Assert::assertNotNull($statId);

        $rowsBefore = $this->connection->fetchAllAssociative(
            'SELECT id FROM '.MAUTIC_TABLE_PREFIX.'email_stats_open_details WHERE stat_id = :statId ORDER BY id ASC',
            ['statId' => $statId]
        );
        Assert::assertCount(3, $rowsBefore);

        // Drop the first open entry (the lowest numeric key) from the array before writing it back.
        $openDetails = $stat->getOpenDetails();
        $numericKeys = array_values(array_filter(array_keys($openDetails), 'is_int'));
        sort($numericKeys);
        unset($openDetails[$numericKeys[0]]);

        $stat->setOpenDetails($openDetails);
        $this->em->persist($stat);
        $this->em->flush();

        $rowsAfter = $this->connection->fetchAllAssociative(
            'SELECT id FROM '.MAUTIC_TABLE_PREFIX.'email_stats_open_details WHERE stat_id = :statId ORDER BY id ASC',
            ['statId' => $statId]
        );
        Assert::assertCount(2, $rowsAfter);

        $idsBefore = array_column($rowsBefore, 'id');
        $idsAfter  = array_column($rowsAfter, 'id');

        // The surviving rows kept their original ids -- proving they were left untouched rather
        // than deleted and reinserted -- and exactly one row (the dropped open entry) is gone.
        Assert::assertCount(1, array_diff($idsBefore, $idsAfter));
        Assert::assertEmpty(array_diff($idsAfter, $idsBefore));
    }

    public function testSetOpenDetailsReplacesRowsAddedSincePersisting(): void
    {
        $stat = new Stat();
        $stat->setEmailAddress('jane@doe.cz');
        $stat->setDateSent(new \DateTime());
        $stat->addOpenDetails(['datetime' => self::DATE_ONE, 'useragent' => 'UA-persisted']);

        $this->em->persist($stat);
        $this->em->flush();

        $statId = $stat->getId();
        Assert::assertNotNull($statId);

        // Add a second row without flushing, then call setOpenDetails() with content that
        // references neither the already-persisted row nor the one just added.
        $stat->addOpenDetails(['datetime' => self::DATE_TWO, 'useragent' => 'UA-unflushed']);
        $stat->setOpenDetails([
            ['datetime' => '2026-01-03 00:00:00', 'useragent' => 'UA-replacement'],
        ]);
        $this->em->persist($stat);
        $this->em->flush();

        $rows = $this->connection->fetchAllAssociative(
            'SELECT open_detail FROM '.MAUTIC_TABLE_PREFIX.'email_stats_open_details WHERE stat_id = :statId',
            ['statId' => $statId]
        );

        // Neither the persisted row nor the unflushed one survives -- only what was passed in.
        Assert::assertCount(1, $rows);
        Assert::assertStringContainsString('UA-replacement', $rows[0]['open_detail']);
    }

    public function testSetOpenDetailsUpdatesContentOfAKeptRow(): void
    {
        $stat = new Stat();
        $stat->setEmailAddress('edited@doe.cz');
        $stat->setDateSent(new \DateTime());
        $stat->addOpenDetails(['datetime' => self::DATE_ONE, 'useragent' => 'UA-original']);
        $stat->addBounceDetails(['datetime' => self::DATE_TWO, 'reason' => 'Original reason']);

        $this->em->persist($stat);
        $this->em->flush();

        $statId = $stat->getId();
        Assert::assertNotNull($statId);

        // Edit both entries' content in place while keeping their _id, then write back.
        $openDetails                                           = $stat->getOpenDetails();
        $numericKeys                                           = array_values(array_filter(array_keys($openDetails), 'is_int'));
        $openKey                                               = $numericKeys[0];
        $openDetails[$openKey]['useragent']                    = 'UA-edited';
        $openDetails[StatOpenDetail::BOUNCES_KEY][0]['reason'] = self::EDITED_REASON;

        $stat->setOpenDetails($openDetails);
        $this->em->persist($stat);
        $this->em->flush();

        $updated = $stat->getOpenDetails();
        Assert::assertSame('UA-edited', $updated[$openKey]['useragent']);
        Assert::assertSame(self::EDITED_REASON, $updated[StatOpenDetail::BOUNCES_KEY][0]['reason']);

        // Re-query to confirm the stored rows themselves changed, not just the in-memory entities.
        $rows = $this->connection->fetchAllAssociative(
            'SELECT open_detail FROM '.MAUTIC_TABLE_PREFIX.'email_stats_open_details WHERE stat_id = :statId',
            ['statId' => $statId]
        );
        $storedContent = implode(' ', array_column($rows, 'open_detail'));
        Assert::assertStringContainsString('UA-edited', $storedContent);
        Assert::assertStringContainsString(self::EDITED_REASON, $storedContent);
        Assert::assertStringNotContainsString('UA-original', $storedContent);
        Assert::assertStringNotContainsString('Original reason', $storedContent);
    }
}
