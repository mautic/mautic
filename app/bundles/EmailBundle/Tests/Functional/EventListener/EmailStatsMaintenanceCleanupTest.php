<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Functional\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\MaintenanceEvent;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\EmailRepository;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Entity\StatData;
use Mautic\EmailBundle\Entity\StatOpenDetail;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListLead;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class EmailStatsMaintenanceCleanupTest extends MauticMysqlTestCase
{
    private const DAYS_OLD = 30;

    private const COMPACTION_DAYS = 60;

    private Email $email;

    private Lead $contact;

    protected function setUp(): void
    {
        $this->configParams['email_stats_compaction_threshold_days'] = self::COMPACTION_DAYS;

        parent::setUp();

        $this->contact = new Lead();
        $this->contact->setEmail('existing@contact.email');

        $segment = new LeadList();
        $segment->setName('Segment A');
        $segment->setPublicName('Segment A');
        $segment->setAlias('segment-a');

        $segmentRef = new ListLead();
        $segmentRef->setLead($this->contact);
        $segmentRef->setList($segment);
        $segmentRef->setDateAdded(new \DateTime());

        $this->email = new Email();
        $this->email->setName('Email A');
        $this->email->setSubject('Email A Subject');
        $this->email->setEmailType('list');
        $this->email->setIsPublished(true);
        $this->email->addList($segment);

        $this->em->persist($segment);
        $this->em->persist($this->contact);
        $this->em->persist($segmentRef);
        $this->em->persist($this->email);
        $this->em->flush();
    }

    public function testOldStatsAreKeptWhetherOrNotTheirContactOrEmailStillExists(): void
    {
        $existingStat       = $this->createStat($this->contact, $this->email, self::COMPACTION_DAYS + 10);
        $deletedContactStat = $this->createStat(null, $this->email, self::COMPACTION_DAYS + 10);
        $deletedEmailStat   = $this->createStat($this->contact, null, self::COMPACTION_DAYS + 10);

        $this->runCleanup(false);

        $this->assertStatExists($existingStat, true);
        $this->assertStatExists($deletedContactStat, true);
        $this->assertStatExists($deletedEmailStat, true);
        $this->assertSame(0, (int) $this->getEmailRepository()->getEmailPendingLeads($this->email->getId(), null, null, true));
    }

    public function testCompactionRemovesDataAndOpenDetailsOfOldStatsButKeepsTheStat(): void
    {
        $oldStat    = $this->createStat($this->contact, $this->email, self::COMPACTION_DAYS + 10);
        $recentStat = $this->createStat($this->contact, $this->email, self::COMPACTION_DAYS - 10);
        $this->createChildRows($oldStat);
        $this->createChildRows($recentStat);

        $this->runCleanup(false);

        $this->assertStatExists($oldStat, true);
        $this->assertSame(0, $this->countChildRows(StatData::class, $oldStat));
        $this->assertSame(0, $this->countChildRows(StatOpenDetail::class, $oldStat));
        $this->assertSame(1, $this->countChildRows(StatData::class, $recentStat));
        $this->assertSame(1, $this->countChildRows(StatOpenDetail::class, $recentStat));
    }

    public function testDryRunCountsCompactableRowsWithoutDeletingThem(): void
    {
        $oldStat = $this->createStat($this->contact, $this->email, self::COMPACTION_DAYS + 10);
        $this->createChildRows($oldStat);

        $stats = $this->runCleanup(true);

        $this->assertSame(1, $stats[$this->trans('mautic.maintenance.email_stats_data')]);
        $this->assertSame(1, $stats[$this->trans('mautic.maintenance.email_stats_open_details')]);
        $this->assertSame(1, $this->countChildRows(StatData::class, $oldStat));
        $this->assertSame(1, $this->countChildRows(StatOpenDetail::class, $oldStat));
    }

    private function createStat(?Lead $contact, ?Email $email, int $daysAgo): Stat
    {
        $stat = new Stat();
        $stat->setEmail($email);
        $stat->setLead($contact);
        $stat->setEmailAddress('stat@contact.email');
        $stat->setDateSent(new \DateTime($daysAgo.' days ago', new \DateTimeZone('UTC')));

        $this->em->persist($stat);
        $this->em->flush();

        return $stat;
    }

    private function createChildRows(Stat $stat): void
    {
        $stat->addOpenDetails(['useragent' => 'Test']);
        $this->em->persist($stat);
        $this->em->flush();

        $this->assertSame(1, $this->countChildRows(StatData::class, $stat));
        $this->assertSame(1, $this->countChildRows(StatOpenDetail::class, $stat));
    }

    /**
     * @return array<string, int>
     */
    private function runCleanup(bool $dryRun): array
    {
        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = self::getContainer()->get(EventDispatcherInterface::class);
        $event      = $dispatcher->dispatch(new MaintenanceEvent(self::DAYS_OLD, $dryRun, false), CoreEvents::MAINTENANCE_CLEANUP_DATA);
        $this->em->clear();

        return $event->getStats();
    }

    private function assertStatExists(Stat $stat, bool $expected): void
    {
        $exists = (bool) $this->em->getConnection()->fetchOne(
            'SELECT 1 FROM '.$this->em->getClassMetadata(Stat::class)->getTableName().' WHERE id = ?',
            [$stat->getId()]
        );
        $this->assertSame($expected, $exists);
    }

    /**
     * @param class-string $entityClass
     */
    private function countChildRows(string $entityClass, Stat $stat): int
    {
        return (int) $this->em->getConnection()->fetchOne(
            'SELECT COUNT(*) FROM '.$this->em->getClassMetadata($entityClass)->getTableName().' WHERE stat_id = ?',
            [$stat->getId()]
        );
    }

    private function getEmailRepository(): EmailRepository
    {
        return $this->em->getRepository(Email::class);
    }

    private function trans(string $key): string
    {
        return self::getContainer()->get(TranslatorInterface::class)->trans($key);
    }
}
