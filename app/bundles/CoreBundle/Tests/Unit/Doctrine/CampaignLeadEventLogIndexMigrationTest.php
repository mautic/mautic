<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\Migrations\Version20260726100000;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class CampaignLeadEventLogIndexMigrationTest extends TestCase
{
    public function testReplacesLeadFirstCampaignLeadsIndex(): void
    {
        $migration = $this->createMigration();
        $schema    = $this->createSchema(['lead_id', 'campaign_id', 'rotation']);

        $migration->preUp($schema);
        $migration->up($schema);

        $this->assertSame(
            'ALTER TABLE campaign_lead_event_log DROP INDEX campaign_leads, ADD INDEX campaign_leads (campaign_id, lead_id, rotation);',
            $migration->getSql()[0]->getStatement()
        );
    }

    public function testSkipsWhenCampaignFirstIndexAlreadyExists(): void
    {
        $migration = $this->createMigration();
        $schema    = $this->createSchema(['campaign_id', 'lead_id', 'rotation'], 'legacy_campaign_leads');

        $this->expectException(SkipMigration::class);

        $migration->preUp($schema);
    }

    public function testCreatesIndexWhenCampaignLeadsIndexIsMissing(): void
    {
        $migration = $this->createMigration();
        $schema    = new Schema();
        $schema->createTable('campaign_lead_event_log');

        $migration->up($schema);

        $this->assertSame(
            'CREATE INDEX campaign_leads ON campaign_lead_event_log (campaign_id, lead_id, rotation);',
            $migration->getSql()[0]->getStatement()
        );
    }

    public function testDownRestoresLeadFirstIndex(): void
    {
        $migration = $this->createMigration();
        $schema    = $this->createSchema(['campaign_id', 'lead_id', 'rotation']);

        $migration->down($schema);

        $this->assertSame(
            'ALTER TABLE campaign_lead_event_log DROP INDEX campaign_leads, ADD INDEX campaign_leads (lead_id, campaign_id, rotation);',
            $migration->getSql()[0]->getStatement()
        );
    }

    /**
     * @param string[] $indexColumns
     */
    private function createSchema(array $indexColumns, string $indexName = 'campaign_leads'): Schema
    {
        $schema = new Schema();
        $table  = $schema->createTable('campaign_lead_event_log');
        $table->addColumn('campaign_id', 'integer');
        $table->addColumn('lead_id', 'integer');
        $table->addColumn('rotation', 'integer');
        $table->addIndex($indexColumns, $indexName);

        return $schema;
    }

    private function createMigration(): Version20260726100000
    {
        $connection = $this->createStub(Connection::class);
        $connection->method('createSchemaManager')->willReturn($this->createStub(AbstractSchemaManager::class));
        $connection->method('getDatabasePlatform')->willReturn(new MySQLPlatform());

        $migration = new Version20260726100000($connection, new NullLogger());
        $migration->setPrefix('');

        return $migration;
    }
}
