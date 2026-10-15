<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\Migrations\Version20201026101117;
use Mautic\Migrations\Version20260120130000;
use Mautic\Migrations\Version20261015170000;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class PostgreSqlCompatibilityMigrationTest extends TestCase
{
    #[DataProvider('migrationDirections')]
    public function testSkipsMySqlWithoutAbortingMigrationBatch(string $direction): void
    {
        $connection = $this->createStub(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn(new MySQLPlatform());
        $migration = new Version20260120130000($connection, new NullLogger());

        $this->expectException(SkipMigration::class);
        $migration->{$direction}(new Schema());
    }

    #[DataProvider('migrationDirections')]
    public function testQueuesPostgreSqlStatements(string $direction): void
    {
        $connection = $this->createStub(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn(new PostgreSQLPlatform());
        $migration = new Version20260120130000($connection, new NullLogger());
        $migration->{$direction}(new Schema());

        $this->assertNotEmpty($migration->getSql());
    }

    public function testPostgreSqlSkipsMySqlCharsetConversionWithExplicitCollation(): void
    {
        $connection = $this->createStub(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn(new PostgreSQLPlatform());
        $migration = new Version20201026101117($connection, new NullLogger());
        $migration->setPrefix('test_');
        $schema = new Schema();
        $schema->createTable('test_emails')->addColumn('subject', 'string', ['platformOptions' => ['collation' => 'en_US.utf8']]);
        $migration->up($schema);
        $this->assertSame([], $migration->getSql());

        $this->expectException(SkipMigration::class);
        $migration->preUp($schema);
    }

    public function testPostgreSqlLegacyNameMigrationSkipsMySql(): void
    {
        $connection = $this->createStub(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn(new MySQLPlatform());
        $migration = new Version20261015170000($connection, new NullLogger());

        $this->expectException(SkipMigration::class);
        $migration->up(new Schema());
    }

    public static function migrationDirections(): iterable
    {
        yield 'up' => ['up'];
        yield 'down' => ['down'];
    }
}
