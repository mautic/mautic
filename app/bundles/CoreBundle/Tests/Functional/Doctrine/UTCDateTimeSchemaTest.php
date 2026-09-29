<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Doctrine;

use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class UTCDateTimeSchemaTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    #[DataProvider('provideColumns')]
    public function testSchemaComparisonPreservesFractionalPrecision(int $storedPrecision, bool $nullable): void
    {
        $connection = $this->em->getConnection();
        $tableName  = MAUTIC_TABLE_PREFIX.'datetime_precision_check';
        $nullSql    = $nullable ? 'DEFAULT NULL' : 'NOT NULL';
        $connection->executeStatement("CREATE TABLE $tableName (occurred_at DATETIME($storedPrecision) $nullSql)");

        try {
            $manager  = $connection->createSchemaManager();
            $platform = $connection->getDatabasePlatform();
            $expected = new Table($tableName);
            $expected->addColumn('occurred_at', Types::DATETIME_MUTABLE, ['precision' => 3, 'notnull' => !$nullable]);
            $actual = $manager->introspectTable($tableName);
            $diff   = $manager->createComparator()->compareTables($actual, $expected);

            $this->assertSame(3 === $storedPrecision, $diff->isEmpty());
            $this->assertSame(!$nullable, $actual->getColumn('occurred_at')->getNotnull());

            foreach ($platform->getAlterTableSQL($diff) as $sql) {
                $connection->executeStatement($sql);
            }

            $upgraded = $manager->introspectTable($tableName);
            $this->assertTrue($manager->createComparator()->compareTables($upgraded, $expected)->isEmpty());
            $this->assertSame(!$nullable, $upgraded->getColumn('occurred_at')->getNotnull());

            $connection->insert($tableName, ['occurred_at' => '2025-11-28 12:00:00.123']);
            $this->assertSame('2025-11-28 12:00:00.123', $connection->fetchOne("SELECT occurred_at FROM $tableName"));
        } finally {
            $connection->executeStatement("DROP TABLE $tableName");
        }
    }

    /**
     * @return iterable<string, array{int, bool}>
     */
    public static function provideColumns(): iterable
    {
        yield 'matching required column' => [3, false];
        yield 'matching nullable column' => [3, true];
        yield 'upgrade required column' => [0, false];
        yield 'upgrade nullable column' => [0, true];
    }
}
