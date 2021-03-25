<?php

declare(strict_types=1);

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\InstallBundle\Tests\InstallFixtures\ORM;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\InstallBundle\InstallFixtures\ORM\RemoveDuplicateIndexData;
use PHPUnit\Framework\Assert;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RemoveDuplicateIndexDataTest extends MauticMysqlTestCase
{
    /**
     * Disable transaction rollback for cleanup as we alter the DB schema within the test.
     *
     * @var bool
     */
    protected $useCleanupRollback = false;

    /**
     * @var RemoveDuplicateIndexData
     */
    private $fixture;

    private $hadAssetsXrefTableBeforeTest = false;
    private $hadListXrefTableBeforeTest   = false;
    protected ContainerInterface $tempContainer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempContainer = self::$container;
        $this->fixture       = new RemoveDuplicateIndexData();
        $this->fixture->setContainer($this->getContainerFake());
    }

    /**
     * We only want to drop the email_assets_xref and email_list_xref tables
     * if we created them in this test, otherwise they should stay for other tests.
     * This prevents errors like "Table 'mautictest.email_list_xref' doesn't exist" in CI.
     */
    protected function tearDown(): void
    {
        if ($this->hadAssetsXrefTableBeforeTest) {
            $this->dropTable('email_assets_xref');
        }
        if ($this->hadListXrefTableBeforeTest) {
            $this->dropTable('email_list_xref');
        }

        parent::tearDown();
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

    private function dropTable(string $table): void
    {
        $this->connection->exec(sprintf('DROP TABLE IF EXISTS %s', $table));
    }

    private function createTables(): void
    {
        $this->hadAssetsXrefTableBeforeTest = ($this->connection->exec('
            CREATE TABLE IF NOT EXISTS email_assets_xref
            (
                email_id int unsigned not null,
                asset_id int unsigned not null,
                primary key (email_id, asset_id),
                INDEX IDX_asset_id (asset_id),
                INDEX IDX_email_id (email_id)
            )
        ') > 0) ? false : true;

        $this->hadListXrefTableBeforeTest = ($this->connection->exec('
            CREATE TABLE IF NOT EXISTS email_list_xref
            (
                email_id int unsigned not null,
                leadlist_id int unsigned not null,
                primary key (email_id, leadlist_id),
                INDEX IDX_email_id (email_id),
                INDEX IDX_leadlist_id (leadlist_id)
            )
        ') > 0) ? false : true;
    }

    private function hasTableIndexForColumn(string $table, string $column): bool
    {
        $query = sprintf('SHOW INDEX FROM %s WHERE Key_name <> "PRIMARY" AND Column_name = "%s"', $table, $column);

        return false !== $this->connection->fetchAssoc($query);
    }

    private function getContainerFake(): ContainerInterface
    {
        return new class($this->tempContainer) implements ContainerInterface {
            /**
             * @var ContainerInterface
             */
            private $container;

            public function __construct(ContainerInterface $container)
            {
                $this->container = $container;
            }

            public function set($id, $service)
            {
                $this->container->set($id, $service);
            }

            public function get($id, $invalidBehavior = self::EXCEPTION_ON_INVALID_REFERENCE)
            {
                return $this->container->get($id, $invalidBehavior);
            }

            public function has($id)
            {
                return $this->container->has($id);
            }

            public function initialized($id)
            {
                return $this->container->initialized($id);
            }

            public function getParameter($name)
            {
                return $this->container->getParameter($name);
            }

            public function hasParameter($name)
            {
                return $this->container->hasParameter($name);
            }

            public function setParameter($name, $value)
            {
                $this->container->setParameter($name, $value);
            }
        };
    }
}
