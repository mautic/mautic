<?php

/*
 * @package     Mautic
 * @copyright   2017 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Migrations\SkipMigrationException;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170216221648 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        if ($schema->getTable($this->prefix.'focus')->hasColumn('css') && $schema->getTable($this->prefix.'focus')->hasColumn('html') && $schema->getTable($this->prefix.'focus')->hasColumn('html_mode')) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("ALTER TABLE {$this->prefix}focus ADD css LONGTEXT NULL");
        $this->addSql('ALTER TABLE '.$this->prefix.'focus ADD html_mode TINYINT(1) DEFAULT 0');
        $this->addSql("ALTER TABLE {$this->prefix}focus ADD html LONGTEXT NULL");
    }
}
