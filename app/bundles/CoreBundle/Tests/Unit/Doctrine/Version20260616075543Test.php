<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\Migrations\Version20260616075543;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class Version20260616075543Test extends TestCase
{
    #[DataProvider('categoryIdTypeProvider')]
    public function testCategoryIdMatchesReferencedColumn(bool $unsigned, string $expectedType): void
    {
        $schema = new Schema();
        $categories = $schema->createTable('categories');
        $categories->addColumn('id', 'integer', ['unsigned' => $unsigned]);

        $migration = $this->createMigration();

        $migration->up($schema);

        $this->assertCount(2, $migration->getSql());
        $this->assertStringContainsString("`category_id`         {$expectedType} DEFAULT NULL", $migration->getSql()[0]->getStatement());
    }

    public function testRepairsExistingTableWithoutForeignKey(): void
    {
        $schema = new Schema();
        $categories = $schema->createTable('categories');
        $categories->addColumn('id', 'integer', ['unsigned' => false]);
        $pointInsights = $schema->createTable('point_insights');
        $pointInsights->addColumn('category_id', 'integer', ['unsigned' => true, 'notnull' => false]);

        $migration = $this->createMigration();
        $migration->up($schema);

        $this->assertCount(2, $migration->getSql());
        $this->assertSame(
            'ALTER TABLE `point_insights` MODIFY `category_id` INT DEFAULT NULL',
            $migration->getSql()[0]->getStatement()
        );
        $this->assertStringContainsString('ADD CONSTRAINT FK_9894AA2612469DE2', $migration->getSql()[1]->getStatement());
    }

    public function testSkipsWhenExistingTableAndForeignKeyExist(): void
    {
        $schema = new Schema();
        $categories = $schema->createTable('categories');
        $categories->addColumn('id', 'integer', ['unsigned' => false]);
        $pointInsights = $schema->createTable('point_insights');
        $pointInsights->addColumn('category_id', 'integer', ['unsigned' => false, 'notnull' => false]);
        $pointInsights->addForeignKeyConstraint(
            $categories,
            ['category_id'],
            ['id'],
            ['onDelete' => 'SET NULL'],
            'FK_9894AA2612469DE2'
        );

        $migration = $this->createMigration();

        $this->expectException(SkipMigration::class);
        $migration->preUp($schema);
    }

    private function createMigration(): Version20260616075543
    {
        $connection = $this->createStub(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn(new MySQLPlatform());

        $migration = new Version20260616075543($connection, new NullLogger());
        $migration->setPrefix('');

        return $migration;
    }

    /**
     * @return iterable<string, array{bool, string}>
     */
    public static function categoryIdTypeProvider(): iterable
    {
        yield 'signed category id' => [false, 'INT'];
        yield 'unsigned category id' => [true, 'INT UNSIGNED'];
    }
}
