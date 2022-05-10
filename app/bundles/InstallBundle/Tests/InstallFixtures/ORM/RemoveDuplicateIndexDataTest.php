<?php

declare(strict_types=1);

namespace Mautic\InstallBundle\Tests\InstallFixtures\ORM;

use Doctrine\DBAL\Exception;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\InstallBundle\InstallFixtures\ORM\RemoveDuplicateIndexData;
use PHPUnit\Framework\Assert;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RemoveDuplicateIndexDataTest extends MauticMysqlTestCase
{
    use FakeContainerTrait;

    protected $useCleanupRollback = false;

    private RemoveDuplicateIndexData $fixture;

    protected ContainerInterface $tempContainer;

    protected function setUp(): void
    {
        parent::setUp();

        defined('MAUTIC_TABLE_PREFIX') || define('MAUTIC_TABLE_PREFIX', '');

        $this->tempContainer = self::$container;
        $this->fixture       = new RemoveDuplicateIndexData();
        $this->fixture->setContainer($this->getContainerFake());
    }

    public function testGetGroups(): void
    {
        Assert::assertSame(['group_install', 'group_mautic_install_data'], RemoveDuplicateIndexData::getGroups());
    }

    public function testGetOrder(): void
    {
        Assert::assertSame(6, $this->fixture->getOrder());
    }

    public function testLoad(): void
    {
        $this->createTables();

        Assert::assertTrue($this->hasTableIndexForColumn('email_assets_xref', 'email_id'));
        Assert::assertTrue($this->hasTableIndexForColumn('email_assets_xref', 'asset_id'));
        Assert::assertTrue($this->hasTableIndexForColumn('email_list_xref', 'email_id'));
        Assert::assertTrue($this->hasTableIndexForColumn('email_list_xref', 'leadlist_id'));

        $this->fixture->load($this->em);

        Assert::assertFalse($this->hasTableIndexForColumn('email_assets_xref', 'email_id'));
        Assert::assertTrue($this->hasTableIndexForColumn('email_assets_xref', 'asset_id'));
        Assert::assertFalse($this->hasTableIndexForColumn('email_list_xref', 'email_id'));
        Assert::assertTrue($this->hasTableIndexForColumn('email_list_xref', 'leadlist_id'));
    }

    /**
     * @throws Exception
     */
    private function createTables(): void
    {
        $this->connection->executeStatement('DROP TABLE IF EXISTS '.MAUTIC_TABLE_PREFIX.'email_assets_xref');
        $this->connection->executeStatement('
            CREATE TABLE IF NOT EXISTS '.MAUTIC_TABLE_PREFIX.'email_assets_xref
            (
                email_id int unsigned not null,
                asset_id int unsigned not null,
                primary key (email_id, asset_id),
                INDEX IDX_asset_id (asset_id),
                INDEX IDX_email_id (email_id)
            )
        ');

        $this->connection->executeStatement('DROP TABLE IF EXISTS '.MAUTIC_TABLE_PREFIX.'email_list_xref');
        $this->connection->executeStatement('
            CREATE TABLE IF NOT EXISTS '.MAUTIC_TABLE_PREFIX.'email_list_xref
            (
                email_id int unsigned not null,
                leadlist_id int unsigned not null,
                primary key (email_id, leadlist_id),
                INDEX IDX_email_id (email_id),
                INDEX IDX_leadlist_id (leadlist_id)
            )
        ');
    }

    /**
     * @throws Exception
     */
    private function hasTableIndexForColumn(string $table, string $column): bool
    {
        $query = sprintf('SHOW INDEX FROM %s WHERE Key_name <> "PRIMARY" AND Column_name = "%s"', MAUTIC_TABLE_PREFIX.$table, $column);

        return false !== $this->connection->fetchAssociative($query);
    }
}
