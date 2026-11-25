<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Doctrine;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CategoryBundle\Entity\Category;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\Migrations\Version20260616075543;
use Psr\Log\NullLogger;

final class Version20260616075543FunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    public function testRepairsOrphanedCategoryReferencesBeforeAddingForeignKey(): void
    {
        $tableName = $this->getTablePrefix().'point_insights';

        $category = new Category();
        $category->setTitle('Migration test category');
        $category->setAlias('migration-test-category');
        $category->setBundle('core');
        $this->em->persist($category);
        $this->em->flush();

        $this->connection->executeStatement("DROP TABLE IF EXISTS `{$tableName}`");
        $this->connection->executeStatement("CREATE TABLE `{$tableName}` (
            `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
            `category_id` INT UNSIGNED DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE = InnoDB");
        $this->connection->insert($tableName, ['category_id' => $category->getId()]);
        $this->connection->insert($tableName, ['category_id' => 2147483647]);
        $this->connection->insert($tableName, ['category_id' => null]);

        try {
            $schema    = $this->connection->createSchemaManager()->introspectSchema();
            $migration = new Version20260616075543($this->connection, new NullLogger());
            $migration->setPrefix($this->getTablePrefix());
            $migration->up($schema);

            foreach ($migration->getSql() as $sql) {
                $this->connection->executeStatement($sql->getStatement());
            }

            $rows = $this->connection->fetchAllAssociative("SELECT category_id FROM `{$tableName}` ORDER BY id");
            $this->assertSame((string) $category->getId(), (string) $rows[0]['category_id']);
            $this->assertNull($rows[1]['category_id']);
            $this->assertNull($rows[2]['category_id']);

            $foreignKeys = $this->connection->createSchemaManager()->listTableForeignKeys($tableName);
            $this->assertTrue(array_filter(
                $foreignKeys,
                fn ($foreignKey): bool => ['category_id'] === $foreignKey->getLocalColumns()
                    && $this->getTablePrefix().'categories' === $foreignKey->getForeignTableName()
            ) !== []);
        } finally {
            $this->connection->executeStatement("DROP TABLE IF EXISTS `{$tableName}`");
        }
    }

    private function getTablePrefix(): string
    {
        return (string) self::getContainer()->getParameter('mautic.db_table_prefix');
    }
}
