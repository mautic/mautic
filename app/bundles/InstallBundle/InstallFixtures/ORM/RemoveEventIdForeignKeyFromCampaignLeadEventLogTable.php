<?php

declare(strict_types=1);

namespace Mautic\InstallBundle\InstallFixtures\ORM;

use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\DBAL\Schema\SchemaException as DoctrineSchemaException;
use Doctrine\Persistence\ObjectManager;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CoreBundle\Exception\SchemaException;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RemoveEventIdForeignKeyFromCampaignLeadEventLogTable extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface, FixtureGroupInterface
{
    private const COLUMN     = ['event_id'];
    private const ORDER      = 8;

    private $container;

    public function setContainer(ContainerInterface $container = null): void
    {
        $this->container = $container;
    }

    public static function getGroups(): array
    {
        return ['group_install', 'group_mautic_install_data'];
    }

    /**
     * @throws SchemaException
     * @throws DoctrineSchemaException
     */
    public function load(ObjectManager $manager): void
    {
        $table = $this->container->getParameter('mautic.db_table_prefix').LeadEventLog::TABLE_NAME;
        $manager->getConnection()->getSchemaManager()->dropForeignKey($this->getForeignKeyName($table), $table);
    }

    private function getForeignKeyName(string $tableName): ?string
    {
        $columnNames = array_merge([$tableName], self::COLUMN);
        $hash        = implode(
            '',
            array_map(
                function ($column) {
                    return dechex(crc32($column));
                },
                $columnNames
            )
        );

        return substr(strtoupper('fk_'.$hash), 0, 63);
    }

    public function getOrder(): int
    {
        return self::ORDER;
    }
}
