<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Doctrine;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class AbstractMauticMigration extends AbstractMigration implements ContainerAwareInterface
{

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Supported platforms
     *
     * @var array
     */
    protected $supported = array('mysql', 'postgresql', 'mssql', 'sqlite');

    /**
     * Database prefix
     *
     * @var string
     */
    protected $prefix;

    /**
     * Database platform
     *
     * @var string
     */
    protected $platform;

    /**
     * @var \Mautic\CoreBundle\Factory\MauticFactory
     */
    protected $factory;

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

        $function = $this->platform . "Up";
        $this->$function($schema);
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

        $function = $this->platform . "Down";
        $this->$function($schema);
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
        $this->prefix    = $container->getParameter('mautic.db_table_prefix');
        $this->platform  = $this->connection->getDatabasePlatform()->getName();
        $this->factory   = $container->get('mautic.factory');
    }

    abstract public function mysqlUp(Schema $schema);
    abstract public function mysqlDown(Schema $schema);

    abstract public function postgresUp(Schema $schema);
    abstract public function postgresDown(Schema $schema);

    abstract public function mssqlUp(Schema $schema);
    abstract public function mssqlDown(Schema $schema);

    abstract public function sqliteUp(Schema $schema);
    abstract public function sqliteDown(Schema $schema);
}
