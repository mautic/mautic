<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Mautic\Migrations\Version020230615115326;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class HistoricalJsonMigrationTest extends TestCase
{
    public function testPreservesOriginalMySqlNullability(): void
    {
        $connection = $this->createStub(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn(new MySQLPlatform());
        $migration = new Version020230615115326($connection, new NullLogger());
        $migration->setPrefix('test_');
        $migration->up(new Schema());
        $sql = array_map(static fn (\Doctrine\Migrations\Query\Query $query): string => $query->getStatement(), $migration->getSql());

        $this->assertCount(11, $sql);
        foreach (['imports', 'lead_event_log'] as $table) {
            $this->assertContains("ALTER TABLE `test_{$table}` CHANGE `properties` `properties` JSON DEFAULT NULL COMMENT '(DC2Type:json)'", $sql);
        }
        $this->assertContains("ALTER TABLE `test_message_channels` CHANGE `properties` `properties` JSON NOT NULL COMMENT '(DC2Type:json)'", $sql);
    }
}
