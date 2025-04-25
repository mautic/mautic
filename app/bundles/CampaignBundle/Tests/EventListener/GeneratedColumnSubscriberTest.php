<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\EventListener;

use Mautic\CampaignBundle\EventListener\GeneratedColumnSubscriber;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Doctrine\GeneratedColumn\GeneratedColumnInterface;
use Mautic\CoreBundle\Event\GeneratedColumnsEvent;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class GeneratedColumnSubscriberTest extends TestCase
{
    protected function setUp(): void
    {
    }

    public function testGetSubscribedEvents(): void
    {
        $subscribedEvents = [
            CoreEvents::ON_GENERATED_COLUMNS_BUILD => ['onGeneratedColumnsBuild', 0],
        ];

        $this->assertSame($subscribedEvents, GeneratedColumnSubscriber::getSubscribedEvents());
    }

    public function testOnGeneratedColumnsBuild(): void
    {
        $generatedColumnsEvent = new GeneratedColumnsEvent();
        $generatedColumns      = $generatedColumnsEvent->getGeneratedColumns();
        Assert::assertEmpty($generatedColumns);

        $columnSubscriber = new GeneratedColumnSubscriber();
        $columnSubscriber->onGeneratedColumnsBuild($generatedColumnsEvent);
        Assert::assertCount(5, $generatedColumns);

        $generatedColumns = iterator_to_array($generatedColumns);
        $alterTableSql    = 'ALTER TABLE '.MAUTIC_TABLE_PREFIX."campaign_leads ADD generated_date_added_hour DATETIME AS (DATE_FORMAT(date_added, \"%Y-%m-%d %H:00\")) COMMENT '(DC2Type:generated)';
            ALTER TABLE ".MAUTIC_TABLE_PREFIX.'campaign_leads ADD INDEX `'.MAUTIC_TABLE_PREFIX.'generated_date_added_hour_campaign_id_date_added`(generated_date_added_hour, campaign_id, date_added)';
        $this->assertAlterTableSql($alterTableSql, array_shift($generatedColumns));

        $alterTableSql = 'ALTER TABLE '.MAUTIC_TABLE_PREFIX."campaign_leads ADD generated_date_added_day DATE AS (DATE_FORMAT(date_added, \"%Y-%m-%d\")) COMMENT '(DC2Type:generated)';
            ALTER TABLE ".MAUTIC_TABLE_PREFIX.'campaign_leads ADD INDEX `'.MAUTIC_TABLE_PREFIX.'generated_date_added_day_campaign_id`(generated_date_added_day, campaign_id)';
        $this->assertAlterTableSql($alterTableSql, array_shift($generatedColumns));

        $alterTableSql = 'ALTER TABLE '.MAUTIC_TABLE_PREFIX."campaign_leads ADD generated_date_added_week CHAR(7) AS (DATE_FORMAT(date_added, \"%Y %U\")) COMMENT '(DC2Type:generated)';
            ALTER TABLE ".MAUTIC_TABLE_PREFIX.'campaign_leads ADD INDEX `'.MAUTIC_TABLE_PREFIX.'generated_date_added_week_campaign_id_date_added`(generated_date_added_week, campaign_id, date_added)';
        $this->assertAlterTableSql($alterTableSql, array_shift($generatedColumns));

        $alterTableSql = 'ALTER TABLE '.MAUTIC_TABLE_PREFIX."campaign_leads ADD generated_date_added_month CHAR(7) AS (DATE_FORMAT(date_added, \"%Y-%m\")) COMMENT '(DC2Type:generated)';
            ALTER TABLE ".MAUTIC_TABLE_PREFIX.'campaign_leads ADD INDEX `'.MAUTIC_TABLE_PREFIX.'generated_date_added_month_campaign_id_date_added`(generated_date_added_month, campaign_id, date_added)';
        $this->assertAlterTableSql($alterTableSql, array_shift($generatedColumns));

        $alterTableSql = 'ALTER TABLE '.MAUTIC_TABLE_PREFIX."campaign_leads ADD generated_date_added_year YEAR AS (DATE_FORMAT(date_added, \"%Y\")) COMMENT '(DC2Type:generated)';
            ALTER TABLE ".MAUTIC_TABLE_PREFIX.'campaign_leads ADD INDEX `'.MAUTIC_TABLE_PREFIX.'generated_date_added_year_campaign_id_date_added`(generated_date_added_year, campaign_id, date_added)';
        $this->assertAlterTableSql($alterTableSql, array_shift($generatedColumns));
    }

    private function assertAlterTableSql(string $expectedSql, GeneratedColumnInterface $generatedColumn): void
    {
        Assert::assertSame($expectedSql, $generatedColumn->getAlterTableSql());
    }
}
