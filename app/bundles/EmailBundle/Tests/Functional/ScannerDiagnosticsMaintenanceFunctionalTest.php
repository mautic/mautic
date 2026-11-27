<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Functional;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\MaintenanceEvent;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\ScannerDiagnostic;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ScannerDiagnosticsMaintenanceFunctionalTest extends MauticMysqlTestCase
{
    public function testOnlyRowsOlderThanTheCutoffAreDeleted(): void
    {
        $this->createDiagnostic('3 days ago');
        $this->createDiagnostic('4 days ago');
        $this->createDiagnostic('1 day ago');
        $this->createDiagnostic('now');
        $this->em->flush();

        $stats = $this->runCleanup(2, false);

        $this->assertSame(2, $stats[$this->label()]);
        $this->assertSame(2, $this->countRows());
        $this->assertSame(0, $this->countRows(" WHERE date_added <= '".$this->daysAgo(2)."'"));
    }

    public function testDryRunCountsButDeletesNothing(): void
    {
        $this->createDiagnostic('3 days ago');
        $this->createDiagnostic('1 day ago');
        $this->em->flush();

        $stats = $this->runCleanup(2, true);

        $this->assertSame(1, $stats[$this->label()]);
        $this->assertSame(2, $this->countRows());
    }

    public function testNothingIsReportedAsDeletedWhenAllRowsAreRecent(): void
    {
        $this->createDiagnostic('now');
        $this->em->flush();

        $stats = $this->runCleanup(2, false);

        $this->assertSame(0, $stats[$this->label()]);
        $this->assertSame(1, $this->countRows());
    }

    public function testMoreRowsThanOneBatchAreAllDeleted(): void
    {
        $this->createDiagnostic('3 days ago');
        $this->createDiagnostic('now');
        $this->em->flush();

        $table   = $this->table();
        $columns = 'date_added, event_type, email_id, send_key, redirect_id, seconds_after_send, ip_hash, ip_network, user_agent, headers, is_https, bot_ratio_flagged';

        for ($i = 0; $i < 14; ++$i) {
            $this->connection->executeStatement(
                "INSERT INTO {$table} ({$columns}) SELECT {$columns} FROM {$table} WHERE date_added <= :date",
                ['date' => $this->daysAgo(2)]
            );
        }

        $old = $this->countRows(" WHERE date_added <= '".$this->daysAgo(2)."'");
        $this->assertSame(16384, $old);

        $stats = $this->runCleanup(2, false);

        $this->assertSame(16384, $stats[$this->label()]);
        $this->assertSame(1, $this->countRows());
    }

    /**
     * @return array<string, int>
     */
    private function runCleanup(int $days, bool $dryRun): array
    {
        $this->em->clear();

        $dispatcher = self::getContainer()->get(EventDispatcherInterface::class);
        $this->assertInstanceOf(EventDispatcherInterface::class, $dispatcher);

        $event = $dispatcher->dispatch(new MaintenanceEvent($days, $dryRun, false), CoreEvents::MAINTENANCE_CLEANUP_DATA);

        return $event->getStats();
    }

    private function label(): string
    {
        $translator = self::getContainer()->get(TranslatorInterface::class);
        $this->assertInstanceOf(TranslatorInterface::class, $translator);

        $label = $translator->trans('mautic.maintenance.email_scanner_diagnostics');
        $this->assertNotSame('mautic.maintenance.email_scanner_diagnostics', $label, 'The translation is missing.');

        return $label;
    }

    private function createDiagnostic(string $when): void
    {
        $this->em->persist(new ScannerDiagnostic(
            new \DateTimeImmutable($when, new \DateTimeZone('UTC')),
            ScannerDiagnostic::EVENT_CLICK,
            null,
            null,
            null,
            null,
            'a1b2c3d4e5f60718',
            null,
            null,
            [],
            true,
            null,
        ));
    }

    private function countRows(string $where = ''): int
    {
        return (int) $this->connection->fetchOne('SELECT COUNT(*) FROM '.$this->table().$where);
    }

    private function table(): string
    {
        return self::getContainer()->getParameter('mautic.db_table_prefix').ScannerDiagnostic::TABLE_NAME;
    }

    private function daysAgo(int $days): string
    {
        return (new \DateTimeImmutable("{$days} days ago", new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
    }
}
