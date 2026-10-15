<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\Migrations\Version20210211081531;
use Mautic\Migrations\Version20210818090322;
use Mautic\Migrations\Version20210819201726;
use Mautic\Migrations\Version20250805095503;
use Mautic\Migrations\Version20251204081531;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class HistoricalUuidMigrationsTest extends TestCase
{
    /**
     * @param class-string<AbstractMauticMigration> $migrationClass
     */
    #[DataProvider('migrations')]
    public function testAddsAndBackfillsUuid(string $migrationClass, string $tableName, AbstractPlatform $platform): void
    {
        $migration = $this->createMigration($migrationClass, $platform);
        $migration->up(new Schema());
        $sql    = array_map(static fn (\Doctrine\Migrations\Query\Query $query): string => $query->getStatement(), $migration->getSql());
        $table  = $platform->quoteIdentifier('test_'.$tableName);
        $column = $platform->quoteIdentifier('uuid');
        $uuid   = $platform instanceof PostgreSQLPlatform ? 'gen_random_uuid()::text' : 'UUID()';

        $this->assertCount(Version20210211081531::class === $migrationClass ? 48 : 2, $sql);
        $this->assertContains("ALTER TABLE {$table} ADD COLUMN {$column} CHAR(36) DEFAULT NULL", $sql);
        $this->assertContains("UPDATE {$table} SET {$column} = {$uuid} WHERE {$column} IS NULL", $sql);
    }

    /**
     * @param class-string<AbstractMauticMigration> $migrationClass
     */
    #[DataProvider('migrations')]
    public function testSkipsExistingUuid(string $migrationClass, string $tableName, AbstractPlatform $platform): void
    {
        $migration = $this->createMigration($migrationClass, $platform);
        $schema    = new Schema();
        $schema->createTable('test_'.$tableName)->addColumn('uuid', 'string');

        if (Version20210211081531::class === $migrationClass) {
            // The first migration handles each table separately.
            foreach ((new \ReflectionProperty($migrationClass, 'tableList'))->getValue($migration) as $name) {
                if (!$schema->hasTable('test_'.$name)) {
                    $schema->createTable('test_'.$name)->addColumn('uuid', 'string');
                }
            }
            $migration->preUp($schema);
            $migration->up($schema);
            $this->assertSame([], $migration->getSql());

            return;
        }

        $this->expectException(SkipMigration::class);
        $migration->preUp($schema);
    }

    public function testDropsMonitoringUuidOnPostgreSql(): void
    {
        $migration = $this->createMigration(Version20251204081531::class, new PostgreSQLPlatform());
        $migration->down(new Schema());

        $this->assertSame('ALTER TABLE "test_monitoring" DROP COLUMN "uuid"', $migration->getSql()[0]->getStatement());
    }

    public static function migrations(): iterable
    {
        $tables = [
            Version20210211081531::class => 'assets',
            Version20210818090322::class => 'roles',
            Version20210819201726::class => 'permissions',
            Version20250805095503::class => 'point_groups',
            Version20251204081531::class => 'monitoring',
        ];

        foreach ([new MySQLPlatform(), new PostgreSQLPlatform()] as $platform) {
            foreach ($tables as $migrationClass => $tableName) {
                yield $platform::class.' '.$migrationClass => [$migrationClass, $tableName, $platform];
            }
        }
    }

    /**
     * @param class-string<AbstractMauticMigration> $migrationClass
     */
    private function createMigration(string $migrationClass, AbstractPlatform $platform): AbstractMauticMigration
    {
        $connection = $this->createStub(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn($platform);
        $migration = new $migrationClass($connection, new NullLogger());
        $migration->setPrefix('test_');

        return $migration;
    }
}
