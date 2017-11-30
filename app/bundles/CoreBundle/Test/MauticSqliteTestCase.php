<?php

namespace Mautic\CoreBundle\Test;

use Doctrine\ORM\Events;
use Mautic\CoreBundle\Test\DoctrineExtensions\TablePrefix;
use Mautic\InstallBundle\Helper\SchemaHelper;

abstract class MauticSqliteTestCase extends AbstractMauticTestCase
{
    public function setUp()
    {
        parent::setUp();

        if (file_exists($this->getOriginalDatabasePath())) {
            $this->createDatabaseFromFile();
        } else {
            $this->createDatabase();
            $this->applyMigrations();
            $this->installDatabaseFixtures();
            $this->backupOrginalDatabase();
        }
    }

    private function createDatabase()
    {
        // fix problem with prefixes in sqlite
        $tablePrefix = new TablePrefix('prefix_');
        $this->em->getEventManager()->addEventListener(Events::loadClassMetadata, $tablePrefix);

        $dbParams = array_merge($this->container->get('doctrine')->getConnection()->getParams(), [
            'table_prefix'  => null,
            'backup_tables' => 0,
        ]);

        // create schema
        $schemaHelper = new SchemaHelper($dbParams);
        $schemaHelper->setEntityManager($this->em);

        $schemaHelper->createDatabase();
        $schemaHelper->installSchema();

        $this->em->getConnection()->close();
    }

    private function createDatabaseFromFile()
    {
        copy($this->getOriginalDatabasePath(), $this->getDatabasePath());
    }

    private function backupOrginalDatabase()
    {
        copy($this->getDatabasePath(), $this->getOriginalDatabasePath());
    }

    private function getOriginalDatabasePath()
    {
        return $this->getDatabasePath().'.original';
    }

    private function getDatabasePath()
    {
        return $this->container->get('doctrine')->getConnection()->getParams()['path'];
    }
}
