<?php

/*
 * @package     Mautic
 * @copyright   2017 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Migrations\SkipMigrationException;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

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
        $focusTable = $schema->getTable($this->prefix.'focus');

        if (
            $focusTable->hasColumn('editor')
            && $focusTable->hasColumn('html')
            && $focusTable->hasColumn('html_mode')
            && $focusTable->hasColumn('utm_tags')
        ) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("ALTER TABLE {$this->prefix}focus ADD editor LONGTEXT NULL");
        $this->addSql('ALTER TABLE '.$this->prefix.'focus ADD html_mode VARCHAR(255) DEFAULT NULL');
        $this->addSql("ALTER TABLE {$this->prefix}focus ADD html LONGTEXT NULL");
        $this->addSql("ALTER TABLE {$this->prefix}focus ADD utm_tags LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)';");
    }
}
