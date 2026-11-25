<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Functional;

use Doctrine\ORM\PersistentCollection;
use Mautic\CoreBundle\Event\MaintenanceEvent;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Copy;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Entity\StatData;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

final class EmailStatPersonalisationCleanupTest extends MauticMysqlTestCase
{
    private const string BODY = '<html><head></head><body>Hi {contactfield=firstname}</body></html>';

    protected function setUp(): void
    {
        if ('testViewInBrowserSaysAnEmailIsNoLongerAvailableAfterItsPersonalisationWasRemovedEvenWhileTheCleanupIsDisabled' !== $this->name()) {
            $this->configParams['email_stats_compaction_threshold_days'] = 30;
        }

        parent::setUp();
    }

    public function testViewInBrowserRendersStoredPersonalisation(): void
    {
        $stat = $this->createStat('recent', new \DateTime('-1 day'));

        $this->assertViewShows($stat, 'Hi John');
    }

    public function testViewInBrowserRendersPersonalisationStoredInTheLegacyColumn(): void
    {
        $stat = $this->createLegacyStat('legacy', new \DateTime('-29 days'));

        $this->runCleanup(false);

        $this->assertViewShows($stat, 'Hi John');
    }

    public function testViewInBrowserOfAnOldEmailWithLegacyPersonalisationSaysItIsNoLongerAvailableAfterTheCleanup(): void
    {
        $stat = $this->createLegacyStat('legacy-old', new \DateTime('-31 days'));

        $this->assertSame(1, $this->runCleanup(false, 'mautic.maintenance.email_stats_tokens'));

        $this->assertNull($this->connection->fetchOne('SELECT tokens FROM '.MAUTIC_TABLE_PREFIX.'email_stats WHERE id = ?', [$stat->getId()]));
        $this->logoutUser();
        $this->assertViewIsGone($stat);
    }

    public function testViewInBrowserOfAnEmailWhosePersonalisationWasCleanedUpSaysItIsNoLongerAvailable(): void
    {
        $stat = $this->createStat('cleaned', new \DateTime('-31 days'));
        $this->runCleanup(false);

        $this->logoutUser();
        $this->assertViewIsGone($stat);
        $this->assertViewIsGone($stat);
    }

    public function testViewInBrowserOfAnEmailCleanedUpUnderAShorterThresholdSaysItIsNoLongerAvailable(): void
    {
        $stat = $this->createStat('shorter', new \DateTime('-1 day'));
        $this->connection->delete(MAUTIC_TABLE_PREFIX.StatData::TABLE_NAME, ['stat_id' => $stat->getId()]);

        $this->logoutUser();
        $this->assertViewIsGone($stat);
    }

    public function testViewInBrowserSaysAnEmailIsNoLongerAvailableAfterItsPersonalisationWasRemovedEvenWhileTheCleanupIsDisabled(): void
    {
        $stat = $this->createStat('disabled', new \DateTime('-100 days'));
        $this->connection->delete(MAUTIC_TABLE_PREFIX.StatData::TABLE_NAME, ['stat_id' => $stat->getId()]);

        $this->logoutUser();
        $this->assertViewIsGone($stat);
    }

    public function testViewInBrowserOfAnOldEmailSentWithoutPersonalisationSaysItIsNoLongerAvailableAfterTheCleanup(): void
    {
        $old    = $this->createStat('unpersonalised-old', new \DateTime('-31 days'), false);
        $recent = $this->createStat('unpersonalised-recent', new \DateTime('-29 days'), false);

        $this->runCleanup(false);

        $this->assertViewShows($recent, 'Hi {contactfield=firstname}');
        $this->assertViewIsGone($old);
    }

    public function testCleanupRemovesStoredPersonalisationOfOldEmailsButKeepsTheirStats(): void
    {
        $old    = $this->createStat('old', new \DateTime('-31 days'));
        $recent = $this->createStat('new', new \DateTime('-29 days'));

        $this->assertSame(1, $this->runCleanup(true), 'A dry run counts the rows to remove');
        $this->assertSame(2, $this->countDataRows(), 'A dry run removes nothing');

        $this->assertSame(1, $this->runCleanup(false));
        $this->assertSame(1, $this->countDataRows());
        $this->em->clear();
        $this->assertFalse($this->em->find(Stat::class, $old->getId())->hasStoredTokens());
        $this->assertSame(['{contactfield=firstname}' => 'John'], $this->em->find(Stat::class, $recent->getId())->getTokens());
    }

    public function testStoredPersonalisationIsLoadedOnlyWhenRead(): void
    {
        $stat = $this->createStat('lazy', new \DateTime());
        $this->em->clear();

        $loaded = $this->em->find(Stat::class, $stat->getId());
        $this->assertInstanceOf(Stat::class, $loaded);
        $rows   = new \ReflectionProperty(Stat::class, 'dataCollection')->getValue($loaded);

        $this->assertInstanceOf(PersistentCollection::class, $rows);
        $this->assertFalse($rows->isInitialized());
        $this->assertSame(['{contactfield=firstname}' => 'John'], $loaded->getTokens());
    }

    private function createStat(string $hash, \DateTime $dateSent, bool $withTokens = true): Stat
    {
        $email = new Email();
        $email->setName('Personalised email');
        $email->setSubject('Personalised email');
        $email->setTemplate('blank');
        $email->setCustomHtml(self::BODY);

        $copy = new Copy();
        $copy->setId(md5($hash));
        $copy->setDateCreated(new \DateTime());
        $copy->setSubject('Personalised email');
        $copy->setBody(self::BODY);

        $stat = new Stat();
        $stat->setEmail($email);
        $stat->setEmailAddress('john@example.com');
        $stat->setTrackingHash($hash);
        $stat->setStoredCopy($copy);
        $stat->setDateSent($dateSent);
        if ($withTokens) {
            $stat->setTokens(['{contactfield=firstname}' => 'John']);
        }

        $this->em->persist($email);
        $this->em->persist($copy);
        $this->em->persist($stat);
        $this->em->flush();

        return $stat;
    }

    private function createLegacyStat(string $hash, \DateTime $dateSent): Stat
    {
        $stat = $this->createStat($hash, $dateSent, false);
        $this->connection->update(MAUTIC_TABLE_PREFIX.'email_stats', ['tokens' => serialize(['{contactfield=firstname}' => 'John'])], ['id' => $stat->getId()]);

        return $stat;
    }

    private function runCleanup(bool $dryRun, string $statKey = 'mautic.maintenance.email_stats_data'): ?int
    {
        $event = self::getContainer()->get(EventDispatcherInterface::class)->dispatch(new MaintenanceEvent(365, $dryRun, false));
        $key   = self::getContainer()->get(TranslatorInterface::class)->trans($statKey);

        return $event->getStats()[$key] ?? null;
    }

    private function countDataRows(): int
    {
        return (int) $this->connection->fetchOne('SELECT COUNT(*) FROM '.MAUTIC_TABLE_PREFIX.StatData::TABLE_NAME);
    }

    private function assertViewIsGone(Stat $stat): void
    {
        $message = self::getContainer()->get(TranslatorInterface::class)->trans('mautic.email.view_in_browser.no_longer_available');
        // The request must load the stat as stored, not the test's in-memory copy
        $this->em->clear();
        $this->client->request(Request::METHOD_GET, '/email/view/'.$stat->getTrackingHash());
        $response = $this->client->getResponse();

        $this->assertSame(Response::HTTP_GONE, $response->getStatusCode(), (string) $response->getContent());
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString($message, (string) $response->getContent());
        $this->assertStringNotContainsString('{contactfield=firstname}', (string) $response->getContent());

        $this->em->clear();
        $viewed = $this->em->find(Stat::class, $stat->getId());
        $this->assertInstanceOf(Stat::class, $viewed);
        $this->assertFalse($viewed->isRead(), 'Showing that an email is no longer available is not a read');
        $this->assertSame(0, (int) $viewed->getOpenCount());
    }

    private function assertViewShows(Stat $stat, string $expected): void
    {
        $this->logoutUser();
        // The request must load the stat as stored, not the test's in-memory copy
        $this->em->clear();
        $this->client->request(Request::METHOD_GET, '/email/view/'.$stat->getTrackingHash());
        $response = $this->client->getResponse();

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode(), (string) $response->getContent());
        $this->assertStringContainsString($expected, (string) $response->getContent());
    }
}
