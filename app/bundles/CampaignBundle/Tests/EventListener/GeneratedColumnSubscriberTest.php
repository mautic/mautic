<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\EventListener;

use Mautic\CampaignBundle\EventListener\GeneratedColumnSubscriber;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Doctrine\GeneratedColumn\GeneratedColumnInterface;
use Mautic\CoreBundle\Doctrine\GeneratedColumn\GeneratedColumns;
use Mautic\CoreBundle\Doctrine\Provider\VersionProviderInterface;
use Mautic\CoreBundle\Event\GeneratedColumnsEvent;
use PHPUnit\Framework\Assert;
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
        Assert::assertCount(5, $generatedColumns);

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
        Assert::assertCount(0, $generatedColumns);
    }

    private function assertAlterTableSql(string $expectedSql, GeneratedColumnInterface $generatedColumn): void
    {
        Assert::assertSame($expectedSql, $generatedColumn->getAlterTableSql());
    }

    private function createVersionProvider(bool $isMySql): VersionProviderInterface
    {
        return new class($isMySql) implements VersionProviderInterface {
            public function __construct(private bool $isMySql)
            {
            }

            public function getVersion(): string
            {
                return '8.0';
            }

            public function isMariaDb(): bool
            {
                return !$this->isMySql;
            }

            public function isMySql(): bool
            {
                return $this->isMySql;
            }
        };
    }

    private function buildGeneratedColumns(bool $isMySql): GeneratedColumns
    {
        $generatedColumnsEvent = new GeneratedColumnsEvent();
        $generatedColumns      = $generatedColumnsEvent->getGeneratedColumns();
        Assert::assertEmpty($generatedColumns);

        $columnSubscriber = new GeneratedColumnSubscriber($this->createVersionProvider($isMySql));
        $columnSubscriber->onGeneratedColumnsBuild($generatedColumnsEvent);

        return $generatedColumns;
    }
}
