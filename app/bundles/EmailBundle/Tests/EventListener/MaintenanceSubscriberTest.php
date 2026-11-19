<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\EventListener;

use Doctrine\DBAL\DriverManager;
use Mautic\CoreBundle\Event\MaintenanceEvent;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\EmailBundle\EventListener\MaintenanceSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class MaintenanceSubscriberTest extends TestCase
{
    private string $previousTimezone;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousTimezone = date_default_timezone_get();
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->previousTimezone);
        parent::tearDown();
    }

    public function testCompactionThresholdIsComputedInUtcRegardlessOfDefaultTimezone(): void
    {
        // A +14h zone puts local time far enough from UTC that the threshold must be computed in UTC.
        date_default_timezone_set('Pacific/Kiritimati');

        $db = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $db->executeStatement('CREATE TABLE '.MAUTIC_TABLE_PREFIX.'email_stats_open_details (id INTEGER PRIMARY KEY, stat_id INTEGER, date_sent TEXT)');
        $table = MAUTIC_TABLE_PREFIX.'email_stats_data';
        $db->executeStatement("CREATE TABLE $table (stat_id INTEGER, date_sent TEXT)");

        $now                 = new \DateTime('now', new \DateTimeZone('UTC'));
        $correctUtcThreshold = (clone $now)->modify('-10 days');
        // 7 hours inside the UTC threshold, so the row is not yet due for compaction.
        $rowDateSent = (clone $correctUtcThreshold)->modify('+7 hours');

        $db->executeStatement(
            "INSERT INTO $table (stat_id, date_sent) VALUES (1, :dateSent)",
            ['dateSent' => $rowDateSent->format('Y-m-d H:i:s')]
        );

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $coreParametersHelper->method('get')->willReturnCallback(
            fn ($name, $default = null) => 'email_stats_compaction_threshold_days' === $name ? 10 : $default
        );

        $subscriber = new MaintenanceSubscriber($db, $translator, $coreParametersHelper);

        $event = new MaintenanceEvent(0, true, false);
        $subscriber->onDataCleanup($event);

        $this->assertSame(0, $event->getStats()['mautic.maintenance.email_stats_data']);
    }
}
