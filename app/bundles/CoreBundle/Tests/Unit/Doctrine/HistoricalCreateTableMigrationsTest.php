<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\Migrations\Version20201019100000;
use Mautic\Migrations\Version20201029001005;
use Mautic\Migrations\Version20220429091934;
use Mautic\Migrations\Version20240226114528;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class HistoricalCreateTableMigrationsTest extends TestCase
{
    /**
     * @param class-string<AbstractMauticMigration> $migrationClass
     * @param array<string, string>                 $references
     */
    #[DataProvider('migrations')]
    public function testCreatesPortableTable(string $migrationClass, string $tableName, array $references, AbstractPlatform $platform, bool $unsigned): void
    {
        $connection = $this->createStub(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn($platform);
        $migration = new $migrationClass($connection, new NullLogger());
        $migration->setPrefix('test_');
        $schema = new Schema();
        foreach ($references as $referenceTable) {
            $schema->createTable('test_'.$referenceTable)->addColumn('id', 'integer', ['unsigned' => $unsigned]);
        }

        $migration->up($schema);
        $sql = implode("\n", array_map(static fn (\Doctrine\Migrations\Query\Query $query): string => $query->getStatement(), $migration->getSql()));
        $this->assertStringContainsString('CREATE TABLE test_'.$tableName, $sql);
        foreach ($references as $column => $referenceTable) {
            $this->assertStringContainsString(sprintf('FOREIGN KEY (%s) REFERENCES test_%s (id)', $column, $referenceTable), $sql);
            $this->assertStringContainsString($column.' INT'.($unsigned && $platform instanceof MySQLPlatform ? ' UNSIGNED' : '').' NOT NULL', $sql);
        }

        if ('email_list_excluded' === $tableName) {
            $this->assertStringContainsString('PRIMARY KEY(email_id, leadlist_id)', $sql);
            $this->assertSame(2, substr_count($sql, 'ON DELETE CASCADE'));
            $this->assertSame(2, substr_count($sql, 'INDEX'));
        } else {
            $this->assertStringContainsString('PRIMARY KEY(id)', $sql);
            $this->assertStringContainsString($platform instanceof PostgreSQLPlatform ? 'id SERIAL NOT NULL' : 'AUTO_INCREMENT', $sql);
            if ('contact_export_scheduler' === $tableName) {
                $this->assertStringContainsString('(DC2Type:datetime_immutable)', $sql);
                $this->assertStringContainsString('(DC2Type:array)', $sql);
                $this->assertStringContainsString('data '.($platform instanceof PostgreSQLPlatform ? 'TEXT' : 'LONGTEXT').' DEFAULT NULL', $sql);
                $migration->down($schema);
                $this->assertSame('DROP TABLE test_contact_export_scheduler', $migration->getSql()[count($migration->getSql()) - 1]->getStatement());
            } else {
                $this->assertStringContainsString('html '.($platform instanceof PostgreSQLPlatform ? 'TEXT' : 'LONGTEXT').' DEFAULT NULL', $sql);
                $this->assertStringContainsString('template VARCHAR(191) DEFAULT NULL', $sql);
                $this->assertStringContainsString('public_preview '.($platform instanceof PostgreSQLPlatform ? 'BOOLEAN DEFAULT true' : 'TINYINT(1) DEFAULT 1').' NOT NULL', $sql);
                $this->assertStringContainsString('UNIQUE', $sql);
            }
        }

        if ($platform instanceof PostgreSQLPlatform) {
            $this->assertStringNotContainsString('AUTO_INCREMENT', $sql);
            $this->assertStringNotContainsString('UNSIGNED', $sql);
            $this->assertStringNotContainsString('ENGINE', $sql);
            $this->assertStringNotContainsString('`', $sql);
        } else {
            $this->assertStringContainsString('utf8mb4', $sql);
            $this->assertStringContainsString('ENGINE = InnoDB', $sql);
        }
    }

    public static function migrations(): iterable
    {
        $tables = [
            Version20201019100000::class => ['emails_draft', ['email_id' => 'emails']],
            Version20201029001005::class => ['pages_draft', ['page_id' => 'pages']],
            Version20220429091934::class => ['contact_export_scheduler', ['user_id' => 'users']],
            Version20240226114528::class => ['email_list_excluded', ['email_id' => 'emails', 'leadlist_id' => 'lead_lists']],
        ];

        foreach ([new MySQLPlatform(), new PostgreSQLPlatform()] as $platform) {
            foreach ([false, true] as $unsigned) {
                foreach ($tables as $migrationClass => [$tableName, $references]) {
                    yield $platform::class.' '.$migrationClass.' '.($unsigned ? 'unsigned' : 'signed') => [$migrationClass, $tableName, $references, $platform, $unsigned];
                }
            }
        }
    }
}
