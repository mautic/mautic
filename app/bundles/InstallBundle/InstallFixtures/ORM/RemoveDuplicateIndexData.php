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
        /** @var IndexSchemaHelper $indexHelper */
        $indexHelper = $this->container->get('mautic.schema.helper.index');

        foreach ($this->tables as $tableName => $columns) {
            $indexHelper->setName($tableName);

            foreach ($columns as $columnName) {
                $indexSql = <<<SQL
SHOW INDEX FROM $tableName WHERE Column_name = '$columnName' and Key_name <> 'PRIMARY';
SQL;
                $stmt = $manager->getConnection()->prepare($indexSql);
                $stmt->execute();
                $result    = $stmt->fetch(FetchMode::ASSOCIATIVE);
                $indexName = $result['Key_name'] ?? null;

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
}
