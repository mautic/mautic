<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Version20141102181850 extends AbstractMigration implements ContainerAwareInterface
{

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var array
     */
    private $supported = array('mysql', 'postgresql');

    /**
     * @param Schema $schema
     *
     * @return void
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function up(Schema $schema)
    {
        $platform = $this->connection->getDatabasePlatform()->getName();

        // Abort the migration if the platform is unsupported
        $this->abortIf(!in_array($platform, $this->supported), 'The database platform is unsupported for migrations');

        $prefix = $this->container->getParameter('mautic.db_table_prefix');

        switch ($platform) {
            case 'mysql':
                $this->addSql('ALTER TABLE ' . $prefix . 'users ADD city LONGTEXT DEFAULT NULL');
                break;
            case 'postgresql':
                $this->addSql('ALTER TABLE ' . $prefix . 'users ADD city TEXT DEFAULT NULL');
                break;
        }
    }

    /**
     * @param Schema $schema
     *
     * @return void
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function down(Schema $schema)
    {
        $platform = $this->connection->getDatabasePlatform()->getName();

        // Abort the migration if the platform is unsupported
        $this->abortIf(!in_array($platform, $this->supported), 'The database platform is unsupported for migrations');

        $prefix = $this->container->getParameter('mautic.db_table_prefix');

        switch ($platform) {
            case 'mysql':
                $this->addSql('ALTER TABLE ' . $prefix . 'users DROP city');
                break;
            case 'postgresql':
                $this->addSql('ALTER TABLE ' . $prefix . 'users DROP city');
                break;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
}
