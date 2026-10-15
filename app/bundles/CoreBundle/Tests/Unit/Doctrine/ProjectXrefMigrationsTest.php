<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Doctrine;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\EventDispatcher;
use Doctrine\Migrations\InlineParameterFormatter;
use Doctrine\Migrations\Metadata\MigrationPlan;
use Doctrine\Migrations\Metadata\Storage\MetadataStorage;
use Doctrine\Migrations\MigratorConfiguration;
use Doctrine\Migrations\Provider\DBALSchemaDiffProvider;
use Doctrine\Migrations\Version\DbalExecutor;
use Doctrine\Migrations\Version\Direction;
use Doctrine\Migrations\Version\Version;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Stopwatch\Stopwatch;

final class ProjectXrefMigrationsTest extends TestCase
{
    /**
     * @param class-string<AbstractMauticMigration> $migrationClass
     */
    #[DataProvider('migrations')]
    public function testExecutorCreatesPrefixedTableWithoutRedundantIndex(string $migrationClass, string $target, string $column, string $tableName, AbstractPlatform $platform): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn($platform);
        $schemaManager = $platform->createSchemaManager($connection);
        $connection->method('createSchemaManager')->willReturn($schemaManager);
        $connection->expects($this->once())->method('beginTransaction');
        $connection->expects($this->once())->method('commit');
        $connection->expects($this->never())->method('executeQuery');
        $logger    = new NullLogger();
        $migration = new $migrationClass($connection, $logger);
        $migration->setPrefix('upgrade_');
        $schema = new Schema();
        foreach ([$target, 'projects'] as $reference) {
            $table = $schema->createTable('upgrade_'.$reference);
            $table->addColumn('id', 'integer');
            $table->setPrimaryKey(['id']);
        }
        $executor = new DbalExecutor(
            $this->createStub(MetadataStorage::class),
            new EventDispatcher($connection, new EventManager()),
            $connection,
            new DBALSchemaDiffProvider($schemaManager, $platform),
            $logger,
            new InlineParameterFormatter($connection),
            new Stopwatch(),
        );
        $plan          = new MigrationPlan(new Version($migrationClass), $migration, Direction::UP);
        $configuration = (new MigratorConfiguration())->setDryRun(true)->setFromSchema($schema);

        // The real executor computes the schema diff, freezes the migration, and calls postUp().
        $result = $executor->execute($plan, $configuration);

        $this->assertFalse($result->isSkipped());
        $this->assertSame($result, $plan->getResult());
        $this->assertTrue($result->hasSql());
        $table = $result->getToSchema()->getTable('upgrade_'.$tableName);
        $this->assertSame([$column, 'project_id'], $table->getPrimaryKey()->getColumns());
        $this->assertCount(2, $table->getIndexes());
        $this->assertCount(2, $table->getForeignKeys());
        foreach ($table->getIndexes() as $index) {
            $this->assertNotSame([$column], $index->getColumns());
        }
        foreach ($table->getForeignKeys() as $foreignKey) {
            $this->assertSame('CASCADE', $foreignKey->onDelete());
        }
        $sql = implode("\n", array_map(static fn (\Doctrine\Migrations\Query\Query $query): string => $query->getStatement(), $result->getSql()));
        $this->assertStringContainsString('CREATE TABLE upgrade_'.$tableName, $sql);
        $this->assertStringNotContainsString('DROP INDEX', $sql);
        $this->assertFalse($schema->hasTable('upgrade_'.$tableName));
    }

    public static function migrations(): iterable
    {
        $migrations = [
            '20250409150450' => ['lead_lists', 'leadlist_id', 'lead_list_projects_xref'],
            '20250424092044' => ['campaigns', 'campaign_id', 'campaign_projects_xref'],
            '20250425125026' => ['emails', 'email_id', 'email_projects_xref'],
            '20250425134835' => ['sms_messages', 'sms_id', 'sms_projects_xref'],
            '20250428071743' => ['forms', 'form_id', 'form_projects_xref'],
            '20250429133744' => ['messages', 'message_id', 'message_projects_xref'],
            '20250430104345' => ['focus', 'focus_id', 'focus_projects_xref'],
            '20250430113559' => ['assets', 'asset_id', 'asset_projects_xref'],
            '20250430135003' => ['pages', 'page_id', 'page_projects_xref'],
            '20250502071717' => ['companies', 'company_id', 'company_projects_xref'],
            '20250618122722' => ['dynamic_content', 'dynamic_content_id', 'dynamic_content_projects_xref'],
            '20250618122908' => ['stages', 'stage_id', 'stage_projects_xref'],
            '20250618134002' => ['points', 'point_id', 'point_projects_xref'],
            '20250618141525' => ['point_triggers', 'point_trigger_id', 'point_trigger_projects_xref'],
        ];
        foreach ([new MySQLPlatform(), new PostgreSQLPlatform()] as $platform) {
            foreach ($migrations as $version => [$target, $column, $tableName]) {
                yield $platform::class.' '.$version => ['Mautic\\Migrations\\Version'.$version, $target, $column, $tableName, $platform];
            }
        }
    }
}
