<?php

declare(strict_types=1);

namespace Mautic\InstallBundle\InstallFixtures\ORM;

use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\Schema\SchemaException as DoctrineSchemaException;
use Doctrine\Persistence\ObjectManager;
use Mautic\CoreBundle\Doctrine\Helper\IndexSchemaHelper;
use Mautic\CoreBundle\Exception\SchemaException;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RemoveDuplicateIndexData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface, FixtureGroupInterface
{
    private $tables = [
        'email_assets_xref' => ['email_id'],
        'email_list_xref'   => ['email_id'],
    ];

    private $container;

    public static function getGroups(): array
    {
        return ['group_install', 'group_mautic_install_data'];
    }

    public function setContainer(ContainerInterface $container = null): void
    {
        $this->container = $container;
    }

    /**
     * @throws SchemaException
     * @throws DoctrineSchemaException
     */
    public function load(ObjectManager $manager): void
    {
        $prefix = $this->container->getParameter('mautic.db_table_prefix');
        /** @var IndexSchemaHelper $indexHelper */
        $indexHelper = $this->container->get('mautic.schema.helper.index');

        foreach ($this->tables as $table => $columns) {
            $indexHelper->setName($table);
            $table = $prefix.$table;

            foreach ($columns as $columnName) {
                $indexName = $this->getIndexName($table, $columnName, $manager);

                if ($indexName) {
                    $indexHelper->dropIndex($columnName, $indexName)->executeChanges();
                }
            }
        }
    }

    public function getOrder(): int
    {
        return 6;
    }

    private function getIndexName(string $table, string $columnName, ObjectManager $manager): ?string
    {
        $sql  = "SHOW INDEX FROM {$table} WHERE Key_name <> 'PRIMARY' AND Column_name = '{$columnName}'";
        $stmt = $manager->getConnection()->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(FetchMode::ASSOCIATIVE);

        return $result['Key_name'] ?? null;
    }
}
