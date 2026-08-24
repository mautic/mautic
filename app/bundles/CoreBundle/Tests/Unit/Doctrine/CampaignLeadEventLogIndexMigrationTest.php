<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\DatabasePlatform;
use Mautic\Migrations\Version20260726100000;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class CampaignLeadEventLogIndexMigrationTest extends TestCase
{
    private const TABLE_NAME = 'campaign_lead_event_log';
    private const INDEX_NAME = 'campaign_event_lead_ids';

    private AbstractPlatform $platform;

    protected function setUp(): void
    {
        parent::setUp();
        $this->platform = new MySQLPlatform();
    }

    public function testReplacesLeadFirstIndexWithCampaignFirst(): void
    {
        $migration = $this->createMigration(
            $this->indexes(Version20260726100000::LEAD_FIRST_COLUMNS)
        );
        $schema = $this->createSchema(Version20260726100000::LEAD_FIRST_COLUMNS);

        $migration->preUp($schema);
        $migration->up($schema);

        $statements = $this->getStatements($migration);

        $this->assertContains(
            DatabasePlatform::getDropIndexSql($this->platform, self::TABLE_NAME, self::INDEX_NAME),
            $statements,
            'STATEMENTS: '.implode(PHP_EOL, $statements)
        );
        $this->assertContains(
            DatabasePlatform::getCreateIndexSql(
                $this->platform,
                self::TABLE_NAME,
                self::INDEX_NAME,
                Version20260726100000::CAMPAIGN_FIRST_COLUMNS
            ),
            $statements,
            'STATEMENTS: '.implode(PHP_EOL, $statements)
        );
    }

    public function testSkipsWhenCampaignFirstIndexAlreadyExists(): void
    {
        $migration = $this->createMigration(
            $this->indexes(Version20260726100000::CAMPAIGN_FIRST_COLUMNS)
        );
        $schema = $this->createSchema(Version20260726100000::CAMPAIGN_FIRST_COLUMNS);

        $this->expectException(SkipMigration::class);
        $this->expectExceptionMessage('A campaign-first campaign lead event log index already exists');

        $migration->preUp($schema);
    }

    public function testCreatesIndexWhenMissing(): void
    {
        $migration = $this->createMigration([]);
        $schema    = new Schema();
        $schema->createTable(self::TABLE_NAME);

        $migration->up($schema);

        $statements = $this->getStatements($migration);

        $this->assertSame(
            [
                DatabasePlatform::getCreateIndexSql(
                    $this->platform,
                    self::TABLE_NAME,
                    self::INDEX_NAME,
                    Version20260726100000::CAMPAIGN_FIRST_COLUMNS
                ),
            ],
            $statements,
            'STATEMENTS: '.implode(PHP_EOL, $statements)
        );
    }

    public function testDoesNothingWhenCampaignFirstIndexAlreadyExistsInUp(): void
    {
        $migration = $this->createMigration(
            $this->indexes(Version20260726100000::CAMPAIGN_FIRST_COLUMNS)
        );
        $schema = $this->createSchema(Version20260726100000::CAMPAIGN_FIRST_COLUMNS);

        $migration->up($schema);

        $statements = $this->getStatements($migration);

        $this->assertSame([],
            $statements,
            'STATEMENTS: '.implode(PHP_EOL, $statements)
        );
    }

    public function testDownRestoresLeadFirstIndex(): void
    {
        $migration = $this->createMigration(
            $this->indexes(Version20260726100000::CAMPAIGN_FIRST_COLUMNS)
        );
        $schema = $this->createSchema(Version20260726100000::CAMPAIGN_FIRST_COLUMNS);

        $migration->down($schema);

        $statements = $this->getStatements($migration);

        $this->assertContains(
            DatabasePlatform::getDropIndexSql($this->platform, self::TABLE_NAME, self::INDEX_NAME),
            $statements,
            'STATEMENTS: '.implode(PHP_EOL, $statements)
        );
        $this->assertContains(
            DatabasePlatform::getCreateIndexSql(
                $this->platform,
                self::TABLE_NAME,
                self::INDEX_NAME,
                Version20260726100000::LEAD_FIRST_COLUMNS
            ),
            $statements,
            'STATEMENTS: '.implode(PHP_EOL, $statements)
        );
    }

    public function testDownDoesNothingWhenCampaignFirstIndexIsMissing(): void
    {
        $migration = $this->createMigration(
            $this->indexes(Version20260726100000::LEAD_FIRST_COLUMNS)
        );
        $schema = $this->createSchema(Version20260726100000::LEAD_FIRST_COLUMNS);

        $migration->down($schema);

        $this->assertSame([], $this->getStatements($migration));
    }

    /**
     * @param string[] $indexColumns
     */
    private function createSchema(array $indexColumns, string $indexName = self::INDEX_NAME): Schema
    {
        $schema = new Schema();
        $table  = $schema->createTable(self::TABLE_NAME);
        $table->addColumn('campaign_id', 'integer');
        $table->addColumn('lead_id', 'integer');
        $table->addColumn('rotation', 'integer');
        $table->addIndex($indexColumns, $indexName);

        return $schema;
    }

    /**
     * @param string[] $columns
     *
     * @return array<string, Index>
     */
    private function indexes(array $columns, string $name = self::INDEX_NAME): array
    {
        return [
            $name => new Index($name, $columns),
        ];
    }

    /**
     * @param array<string, Index> $indexes
     */
    private function createMigration(array $indexes = []): Version20260726100000
    {
        $schemaManager = $this->createStub(AbstractSchemaManager::class);
        $schemaManager->method('listTableIndexes')->willReturn($indexes);

        $connection = $this->createStub(Connection::class);
        $connection->method('createSchemaManager')->willReturn($schemaManager);
        $connection->method('getDatabasePlatform')->willReturn($this->platform);

        $migration = new Version20260726100000($connection, new NullLogger());
        $migration->setPrefix('');

        return $migration;
    }

    /**
     * @return list<string>
     */
    private function getStatements(Version20260726100000 $migration): array
    {
        return array_map(
            static fn ($query) => $query->getStatement(),
            $migration->getSql()
        );
    }
}
