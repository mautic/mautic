<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Doctrine;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\Migrations\Version20260616075543;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class Version20260616075543Test extends TestCase
{
    #[DataProvider('categoryIdTypeProvider')]
    public function testCategoryIdMatchesReferencedColumn(bool $unsigned, string $expectedType): void
    {
        $schema = new Schema();
        $categories = $schema->createTable('categories');
        $categories->addColumn('id', 'integer', ['unsigned' => $unsigned]);

        $migration = (new \ReflectionClass(Version20260616075543::class))->newInstanceWithoutConstructor();
        $prefix = new \ReflectionProperty(AbstractMauticMigration::class, 'prefix');
        $prefix->setValue($migration, '');

        $plannedSql = new \ReflectionProperty(AbstractMigration::class, 'plannedSql');
        $plannedSql->setValue($migration, []);
        $frozen = new \ReflectionProperty(AbstractMigration::class, 'frozen');
        $frozen->setValue($migration, false);

        $migration->up($schema);

        $sql = $plannedSql->getValue($migration);
        $this->assertCount(2, $sql);
        $this->assertStringContainsString("`category_id`         {$expectedType} DEFAULT NULL", (string) $sql[0]);
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
