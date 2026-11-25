<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\EventListener;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Mautic\CoreBundle\Doctrine\Connection\ConnectionWrapper;
use Mautic\CoreBundle\Event\MaintenanceEvent;
use Mautic\EmailBundle\EventListener\MaintenanceSubscriber;
use Mautic\EmailBundle\Helper\EmailConfigInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class MaintenanceSubscriberTest extends TestCase
{
    private string $previousTimezone;

    private Connection $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousTimezone = date_default_timezone_get();

        $this->db = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true, 'wrapperClass' => ConnectionWrapper::class]);
        $this->db->executeStatement('CREATE TABLE '.MAUTIC_TABLE_PREFIX.'email_stats_data (stat_id INTEGER PRIMARY KEY, date_sent TEXT, tokens TEXT)');
        $this->db->executeStatement('CREATE TABLE '.MAUTIC_TABLE_PREFIX.'email_stats (id INTEGER PRIMARY KEY, date_sent TEXT, tokens TEXT)');
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->previousTimezone);
        parent::tearDown();
    }

    public function testThresholdIsComputedInUtcRegardlessOfDefaultTimezone(): void
    {
        // The row is inside the UTC threshold but outside a local one
        date_default_timezone_set('Pacific/Kiritimati');
        $insideThreshold = new \DateTime('now', new \DateTimeZone('UTC'))->modify('-10 days +7 hours');
        $this->insertRow(1, $insideThreshold);

        $event = new MaintenanceEvent(0, true, false);
        $this->createSubscriber(10)->onDataCleanup($event);

        $this->assertSame(0, $event->getStats()['mautic.maintenance.email_stats_data']);
    }

    public function testRowsSentBeforeTheThresholdAreDeleted(): void
    {
        $this->insertRow(1, new \DateTime('-11 days', new \DateTimeZone('UTC')));
        $this->insertRow(2, new \DateTime('-9 days', new \DateTimeZone('UTC')));

        $event = new MaintenanceEvent(0, false, false);
        $this->createSubscriber(10)->onDataCleanup($event);

        $this->assertSame(1, $event->getStats()['mautic.maintenance.email_stats_data']);
        $this->assertSame([2], array_map(intval(...), $this->db->fetchFirstColumn('SELECT stat_id FROM '.MAUTIC_TABLE_PREFIX.'email_stats_data')));
    }

    public function testNothingIsDeletedOrReportedWhenTheCleanupIsDisabled(): void
    {
        $this->insertRow(1, new \DateTime('-1000 days', new \DateTimeZone('UTC')));

        $event = new MaintenanceEvent(0, false, false);
        $this->createSubscriber(0)->onDataCleanup($event);

        $this->assertSame([], $event->getStats());
        $this->assertSame(1, (int) $this->db->fetchOne('SELECT COUNT(*) FROM '.MAUTIC_TABLE_PREFIX.'email_stats_data'));
    }

    public function testLegacyTokensOfStatsSentBeforeTheThresholdAreCleared(): void
    {
        $old = new \DateTime('-11 days', new \DateTimeZone('UTC'));
        $this->insertStat(1, $old, serialize(['{a}' => 'b']));
        $this->insertStat(2, $old, 'N;');
        $this->insertStat(3, $old, null);
        $this->insertStat(4, new \DateTime('-9 days', new \DateTimeZone('UTC')), serialize(['{a}' => 'b']));

        $dryRun = new MaintenanceEvent(0, true, false);
        $this->createSubscriber(10)->onDataCleanup($dryRun);
        $this->assertSame(1, $dryRun->getStats()['mautic.maintenance.email_stats_tokens']);

        $event = new MaintenanceEvent(0, false, false);
        $this->createSubscriber(10)->onDataCleanup($event);

        $this->assertSame(1, $event->getStats()['mautic.maintenance.email_stats_tokens']);
        $tokens = $this->db->fetchAllKeyValue('SELECT id, tokens FROM '.MAUTIC_TABLE_PREFIX.'email_stats ORDER BY id');
        $this->assertSame([1 => null, 2 => 'N;', 3 => null, 4 => serialize(['{a}' => 'b'])], $tokens);
    }

    private function createSubscriber(int $thresholdDays): MaintenanceSubscriber
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        $emailConfig = $this->createStub(EmailConfigInterface::class);
        $emailConfig->method('getStatsCompactionThresholdDays')->willReturn($thresholdDays);

        return new MaintenanceSubscriber($this->db, $translator, $emailConfig);
    }

    private function insertRow(int $statId, \DateTimeInterface $dateSent): void
    {
        $this->db->insert(MAUTIC_TABLE_PREFIX.'email_stats_data', ['stat_id' => $statId, 'date_sent' => $dateSent->format('Y-m-d H:i:s')]);
    }

    private function insertStat(int $id, \DateTimeInterface $dateSent, ?string $tokens): void
    {
        $this->db->insert(MAUTIC_TABLE_PREFIX.'email_stats', ['id' => $id, 'date_sent' => $dateSent->format('Y-m-d H:i:s'), 'tokens' => $tokens]);
    }
}
