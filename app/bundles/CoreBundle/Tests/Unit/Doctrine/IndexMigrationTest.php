<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Index;
use Doctrine\Migrations\Query\Query;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\CoreBundle\Doctrine\DatabasePlatform;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class IndexMigrationTest extends TestCase
{
    /**
     * @param list<string> $operations
     * @param list<string> $expected
     */
    #[DataProvider('indexOperations')]
    public function testMySqlConditionalIndexesRespectQueuedChanges(bool $exists, array $operations, array $expected): void
    {
        $manager = $this->createStub(AbstractSchemaManager::class);
        $manager->method('listTableIndexes')->willReturn($exists ? ['idx' => new Index('idx', ['email'])] : []);
        $connection = $this->createStub(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn(new MySQLPlatform());
        $connection->method('createSchemaManager')->willReturn($manager);
        $migration = new class($connection, new NullLogger()) extends AbstractMauticMigration {};
        foreach ($operations as $operation) {
            $method = new \ReflectionMethod($migration, $operation.'Index');
            $method->invokeArgs($migration, 'create' === $operation ? ['contacts', 'idx', ['email']] : ['contacts', 'idx']);
        }
        $this->assertSame($expected, array_map(static fn (Query $query): string => $query->getStatement(), $migration->getSql()));
    }

    public static function indexOperations(): iterable
    {
        $create = 'CREATE INDEX idx ON contacts (email)';
        $drop = 'DROP INDEX idx ON contacts';
        yield 'existing index creation skipped' => [true, ['create'], []];
        yield 'missing index drop skipped' => [false, ['drop'], []];
        yield 'drop and recreate same index' => [true, ['drop', 'create'], [$drop, $create]];
        yield 'duplicate queued create skipped' => [false, ['create', 'create'], [$create]];
        yield 'duplicate queued drop skipped' => [true, ['drop', 'drop'], [$drop]];
        yield 'new index can be dropped before execution' => [false, ['create', 'drop'], [$create, $drop]];
    }

    public function testNativeIndexConditionalsAreOnlyEmittedOnSupportingPlatforms(): void
    {
        $mysql = new MySQLPlatform();
        $postgres = new PostgreSQLPlatform();
        $this->assertSame('DROP INDEX idx ON contacts', DatabasePlatform::getDropIndexSql($mysql, 'contacts', 'idx', false, true));
        $this->assertSame('CREATE INDEX idx ON contacts (email)', DatabasePlatform::getCreateIndexSql($mysql, 'contacts', 'idx', ['email'], false, false, true));
        $this->assertSame('DROP INDEX IF EXISTS idx', DatabasePlatform::getDropIndexSql($postgres, 'contacts', 'idx', false, true));
        $this->assertSame('CREATE INDEX IF NOT EXISTS idx ON contacts (email)', DatabasePlatform::getCreateIndexSql($postgres, 'contacts', 'idx', ['email'], false, false, true));
    }

    public function testPostgreSqlIndexPrefixLengthsAreRemovedWithoutChangingExpressions(): void
    {
        $columns = ['companyname(191)', 'company_city(64)', 'LOWER(name)', '(LOWER(name))'];
        $this->assertSame('CREATE INDEX idx ON contacts (companyname, company_city, LOWER(name), (LOWER(name)))', DatabasePlatform::getCreateIndexSql(new PostgreSQLPlatform(), 'contacts', 'idx', $columns));
        $this->assertSame('CREATE INDEX idx ON contacts (companyname(191), company_city(64), LOWER(name), (LOWER(name)))', DatabasePlatform::getCreateIndexSql(new MySQLPlatform(), 'contacts', 'idx', $columns));
    }
}
