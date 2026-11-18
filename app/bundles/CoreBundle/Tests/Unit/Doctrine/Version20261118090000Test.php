<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\Migrations\Version20261118090000;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class Version20261118090000Test extends TestCase
{
    public function testNormalizesNullPreferencesAndMakesTheColumnNonNullable(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('createSchemaManager')->willReturn($this->createStub(AbstractSchemaManager::class));
        $connection->method('getDatabasePlatform')->willReturn(new MySQLPlatform());
        $connection->expects($this->once())
            ->method('executeStatement')
            ->with(
                'UPDATE users SET preferences = :emptyPreferences WHERE preferences IS NULL',
                ['emptyPreferences' => 'a:0:{}']
            );

        $migration = new Version20261118090000($connection, new NullLogger());
        $migration->setPrefix('');
        $schema = new Schema();
        $table  = $schema->createTable('users');
        $table->addColumn('preferences', 'text')->setNotnull(false);

        $migration->up($schema);

        $this->assertTrue($table->getColumn('preferences')->getNotnull());
    }

    public function testSkipsWhenTheColumnIsAlreadyNonNullable(): void
    {
        $migration = $this->createMigration();
        $schema    = new Schema();
        $table     = $schema->createTable('users');
        $table->addColumn('preferences', 'text')->setNotnull(true);

        $this->expectException(SkipMigration::class);

        $migration->preUp($schema);
    }

    private function createMigration(): Version20261118090000
    {
        $connection = $this->createStub(Connection::class);
        $connection->method('createSchemaManager')->willReturn($this->createStub(AbstractSchemaManager::class));
        $connection->method('getDatabasePlatform')->willReturn(new MySQLPlatform());

        $migration = new Version20261118090000($connection, new NullLogger());
        $migration->setPrefix('');

        return $migration;
    }
}
