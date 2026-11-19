<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Functional\Entity;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Entity\StatOpenDetail;

final class StatOpenDetailsReconciliationTest extends MauticMysqlTestCase
{
    private const DATE_ONE = '2026-01-01 00:00:00';

    private const DATE_TWO = '2026-01-02 00:00:00';

    private const EDITED_REASON = 'Edited reason';

    public function testSetOpenDetailsReplacesRowsAddedSincePersisting(): void
    {
        $stat = new Stat();
        $stat->setEmailAddress('jane@doe.cz');
        $stat->setDateSent(new \DateTime());
        $stat->addOpenDetails(['datetime' => self::DATE_ONE, 'useragent' => 'UA-persisted']);

        $this->em->persist($stat);
        $this->em->flush();

        $statId = $stat->getId();
        $this->assertNotNull($statId);

        // Add a second row without flushing, then replace everything with different content.
        $stat->addOpenDetails(['datetime' => self::DATE_TWO, 'useragent' => 'UA-unflushed']);
        $stat->setOpenDetails([
            ['datetime' => '2026-01-03 00:00:00', 'useragent' => 'UA-replacement'],
        ]);
        $this->em->persist($stat);
        $this->em->flush();

        $rows = $this->connection->fetchAllAssociative(
            'SELECT open_detail FROM '.$this->em->getClassMetadata(StatOpenDetail::class)->getTableName().' WHERE stat_id = :statId',
            ['statId' => $statId]
        );

        // Neither the persisted row nor the unflushed one survives -- only what was passed in.
        $this->assertCount(1, $rows);
        $this->assertStringContainsString('UA-replacement', (string) $rows[0]['open_detail']);
    }

    public function testSetOpenDetailsStoresEditedContent(): void
    {
        $stat = new Stat();
        $stat->setEmailAddress('edited@doe.cz');
        $stat->setDateSent(new \DateTime());
        $stat->addOpenDetails(['datetime' => self::DATE_ONE, 'useragent' => 'UA-original']);
        $stat->addBounceDetails(['datetime' => self::DATE_TWO, 'reason' => 'Original reason']);

        $this->em->persist($stat);
        $this->em->flush();

        $statId = $stat->getId();
        $this->assertNotNull($statId);

        // Edit both entries' content, then write back.
        $openDetails                                           = $stat->getOpenDetails();
        $numericKeys                                           = array_values(array_filter(array_keys($openDetails), is_int(...)));
        $openKey                                               = $numericKeys[0];
        $openDetails[$openKey]['useragent']                    = 'UA-edited';
        $openDetails[StatOpenDetail::BOUNCES_KEY][0]['reason'] = self::EDITED_REASON;

        $stat->setOpenDetails($openDetails);
        $this->em->persist($stat);
        $this->em->flush();

        $updated = $stat->getOpenDetails();
        $this->assertSame('UA-edited', $updated[$openKey]['useragent']);
        $this->assertSame(self::EDITED_REASON, $updated[StatOpenDetail::BOUNCES_KEY][0]['reason']);

        // Re-query to confirm the stored rows themselves changed, not just the in-memory entities.
        $rows = $this->connection->fetchAllAssociative(
            'SELECT open_detail FROM '.$this->em->getClassMetadata(StatOpenDetail::class)->getTableName().' WHERE stat_id = :statId',
            ['statId' => $statId]
        );
        $storedContent = implode(' ', array_column($rows, 'open_detail'));
        $this->assertStringContainsString('UA-edited', $storedContent);
        $this->assertStringContainsString(self::EDITED_REASON, $storedContent);
        $this->assertStringNotContainsString('UA-original', $storedContent);
        $this->assertStringNotContainsString('Original reason', $storedContent);
    }
}
