<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\EventListener;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Mautic\CampaignBundle\EventListener\GeneratedColumnSubscriber;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Doctrine\GeneratedColumn\GeneratedColumnInterface;
use Mautic\CoreBundle\Doctrine\GeneratedColumn\GeneratedColumns;
use Mautic\CoreBundle\Doctrine\Provider\VersionProviderInterface;
use Mautic\CoreBundle\Event\GeneratedColumnsEvent;
use PHPUnit\Framework\TestCase;

final class GeneratedColumnSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        $subscribedEvents = [
            CoreEvents::ON_GENERATED_COLUMNS_BUILD => ['onGeneratedColumnsBuild', 0],
        ];

        $this->assertSame($subscribedEvents, GeneratedColumnSubscriber::getSubscribedEvents());
    }

    public function testOnGeneratedColumnsBuildWithMySql(): void
    {
        $generatedColumns = $this->buildGeneratedColumns(true);
        $this->assertCount(5, $generatedColumns);

        $generatedColumns = iterator_to_array($generatedColumns);
        $this->assertAlterTableSql('ALTER TABLE '.MAUTIC_TABLE_PREFIX."campaign_leads ADD generated_date_added_hour DATETIME AS (DATE_FORMAT(date_added, \"%Y-%m-%d %H:00\")) STORED COMMENT '(DC2Type:generated)';
            ALTER TABLE ".MAUTIC_TABLE_PREFIX.'campaign_leads ADD INDEX `'.MAUTIC_TABLE_PREFIX.'campaign_id_generated_date_added_hour_date_added`(campaign_id, generated_date_added_hour, date_added)', array_shift($generatedColumns));
        $this->assertAlterTableSql('ALTER TABLE '.MAUTIC_TABLE_PREFIX."campaign_leads ADD generated_date_added_day DATE AS (DATE_FORMAT(date_added, \"%Y-%m-%d\")) STORED COMMENT '(DC2Type:generated)';
            ALTER TABLE ".MAUTIC_TABLE_PREFIX.'campaign_leads ADD INDEX `'.MAUTIC_TABLE_PREFIX.'campaign_id_generated_date_added_day`(campaign_id, generated_date_added_day)', array_shift($generatedColumns));
        $this->assertAlterTableSql('ALTER TABLE '.MAUTIC_TABLE_PREFIX."campaign_leads ADD generated_date_added_week CHAR(7) AS (DATE_FORMAT(date_added, \"%Y %U\")) STORED COMMENT '(DC2Type:generated)';
            ALTER TABLE ".MAUTIC_TABLE_PREFIX.'campaign_leads ADD INDEX `'.MAUTIC_TABLE_PREFIX.'campaign_id_generated_date_added_week_date_added`(campaign_id, generated_date_added_week, date_added)', array_shift($generatedColumns));
        $this->assertAlterTableSql('ALTER TABLE '.MAUTIC_TABLE_PREFIX."campaign_leads ADD generated_date_added_month CHAR(7) AS (DATE_FORMAT(date_added, \"%Y-%m\")) STORED COMMENT '(DC2Type:generated)';
            ALTER TABLE ".MAUTIC_TABLE_PREFIX.'campaign_leads ADD INDEX `'.MAUTIC_TABLE_PREFIX.'campaign_id_generated_date_added_month_date_added`(campaign_id, generated_date_added_month, date_added)', array_shift($generatedColumns));
        $this->assertAlterTableSql('ALTER TABLE '.MAUTIC_TABLE_PREFIX."campaign_leads ADD generated_date_added_year YEAR AS (DATE_FORMAT(date_added, \"%Y\")) STORED COMMENT '(DC2Type:generated)';
            ALTER TABLE ".MAUTIC_TABLE_PREFIX.'campaign_leads ADD INDEX `'.MAUTIC_TABLE_PREFIX.'campaign_id_generated_date_added_year_date_added`(campaign_id, generated_date_added_year, date_added)', array_shift($generatedColumns));
    }

    public function testOnGeneratedColumnsBuildWithMariaDb(): void
    {
        $generatedColumns = $this->buildGeneratedColumns(false);
        $this->assertCount(0, $generatedColumns);
    }

    public function testOnGeneratedColumnsBuildWithPostgreSql(): void
    {
        $generatedColumns = $this->buildGeneratedColumns(false, true);
        $this->assertCount(5, $generatedColumns);
        $expected = [
            'hour' => ['H', 'TIMESTAMP(0) WITHOUT TIME ZONE', "date_trunc('hour', date_added)"],
            'day' => ['d', 'DATE', 'date_added::date'],
            'week' => ['W', 'CHAR(7) COLLATE mautic_unicode_ci', "EXTRACT(YEAR FROM date_added)::int::text || ' ' || LPAD(((EXTRACT(DOY FROM date_added)::int + 6 - EXTRACT(DOW FROM date_added)::int) / 7)::text, 2, '0')"],
            'month' => ['m', 'CHAR(7) COLLATE mautic_unicode_ci', "EXTRACT(YEAR FROM date_added)::int::text || '-' || LPAD(EXTRACT(MONTH FROM date_added)::int::text, 2, '0')"],
            'year' => ['Y', 'INTEGER', 'EXTRACT(YEAR FROM date_added)::int'],
        ];
        $platform = new PostgreSQLPlatform();
        foreach ($generatedColumns as $generatedColumn) {
            $name = substr($generatedColumn->getColumnName(), strlen('generated_date_added_'));
            [$unit, $type, $expression] = $expected[$name];
            $this->assertSame($type.' GENERATED ALWAYS AS ('.$expression.') STORED', $generatedColumn->getColumnDefinition($platform));
            $this->assertSame('date_added', $generatedColumn->getOriginalDateColumn());
            $this->assertSame($unit, $generatedColumn->getTimeUnit());
            $this->assertSame('day' === $name ? $generatedColumn->getColumnName() : null, $generatedColumn->getFilterDateColumn());
            $this->assertSame('day' === $name ? ['campaign_id', $generatedColumn->getColumnName()] : ['campaign_id', $generatedColumn->getColumnName(), 'date_added'], $generatedColumn->getIndexColumns());
        }
    }

    private function assertAlterTableSql(string $expectedSql, GeneratedColumnInterface $generatedColumn): void
    {
        $this->assertSame($expectedSql, $generatedColumn->getAlterTableSql());
    }

    private function createVersionProvider(bool $isMySql, bool $isPostgreSql = false): VersionProviderInterface
    {
        return new class($isMySql, $isPostgreSql) implements VersionProviderInterface {
            public function __construct(
                private readonly bool $isMySql,
                private readonly bool $isPostgreSql,
            ) {
            }

            public function getVersion(): string
            {
                return '8.0';
            }

            public function isMariaDb(): bool
            {
                return !$this->isMySql && !$this->isPostgreSql;
            }

            public function isMySql(): bool
            {
                return $this->isMySql;
            }

            public function isPostgreSql(): bool
            {
                return $this->isPostgreSql;
            }
        };
    }

    private function buildGeneratedColumns(bool $isMySql, bool $isPostgreSql = false): GeneratedColumns
    {
        $generatedColumnsEvent = new GeneratedColumnsEvent();
        $generatedColumns      = $generatedColumnsEvent->getGeneratedColumns();
        $this->assertEmpty($generatedColumns);

        $columnSubscriber = new GeneratedColumnSubscriber($this->createVersionProvider($isMySql, $isPostgreSql));
        $columnSubscriber->onGeneratedColumnsBuild($generatedColumnsEvent);

        return $generatedColumns;
    }
}
