<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Tests\Migrations;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Mautic\Migrations\Version20260908120000;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class Version20260908120000Test extends TestCase
{
    public function testAddsContinueSendingAndBackfillsExistingSchedules(): void
    {
        $schema = new Schema();
        $schema->createTable('sms_messages')->addColumn('publish_up', 'datetime', ['notnull' => false]);
        $migration = $this->createMigration();

        $migration->up($schema);

        $statements = array_map(
            static fn (\Doctrine\Migrations\Query\Query $query): string => $query->getStatement(),
            $migration->getSql(),
        );
        $this->assertSame([
            'ALTER TABLE sms_messages ADD `continue_sending` TINYINT(1) DEFAULT 0 NOT NULL',
            'UPDATE sms_messages SET `continue_sending` = 1 WHERE publish_up IS NOT NULL',
        ], $statements);
    }

    public function testDoesNothingWhenContinueSendingAlreadyExists(): void
    {
        $schema = new Schema();
        $schema->createTable('sms_messages')->addColumn('continue_sending', 'boolean');
        $migration = $this->createMigration();

        $migration->up($schema);

        $this->assertSame([], $migration->getSql());
    }

    public function testDownDropsContinueSendingWhenItExists(): void
    {
        $schema = new Schema();
        $schema->createTable('sms_messages')->addColumn('continue_sending', 'boolean');
        $migration = $this->createMigration();

        $migration->down($schema);

        $this->assertSame('ALTER TABLE sms_messages DROP continue_sending', $migration->getSql()[0]->getStatement());
    }

    private function createMigration(): Version20260908120000
    {
        $connection = $this->createStub(Connection::class);
        $connection->method('createSchemaManager')->willReturn($this->createStub(AbstractSchemaManager::class));
        $connection->method('getDatabasePlatform')->willReturn(new MySQLPlatform());

        $migration = new Version20260908120000($connection, new NullLogger());
        $migration->setPrefix('');

        return $migration;
    }
}
