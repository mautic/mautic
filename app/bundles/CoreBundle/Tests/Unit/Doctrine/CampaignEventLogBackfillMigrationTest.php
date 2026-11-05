<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Doctrine;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Mautic\Migrations\Version20260715144000;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class CampaignEventLogBackfillMigrationTest extends TestCase
{
    public function testBackfillDoesNotWrapBatchesInOneTransaction(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('createSchemaManager')->willReturn($this->createStub(AbstractSchemaManager::class));
        $connection->method('getDatabasePlatform')->willReturn(new MySQLPlatform());
        $connection->expects($this->exactly(3))->method('fetchFirstColumn')->willReturn(range(1, 1000), [1001], []);
        $connection->expects($this->exactly(2))->method('executeStatement')->willReturnCallback(
            function (string $sql, array $params, array $types): int {
                $this->assertSame('UPDATE test_campaign_lead_event_log SET non_action_path_taken = 0 WHERE id IN (?)', $sql);
                $this->assertLessThanOrEqual(1000, count($params[0]));
                $this->assertSame([ArrayParameterType::INTEGER], $types);

                return count($params[0]);
            }
        );

        $migration = new Version20260715144000($connection, new NullLogger());
        $migration->setPrefix('test_');

        $this->assertFalse($migration->isTransactional());
        $migration->up(new Schema());
    }
}
