<?php

/*
 * @package     Mautic
 * @copyright   2018 Mautic Contributors. All rights reserved.
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
class Version20180212084814 extends AbstractMauticMigration
{
    private $keys = ['actionButtonIcon1', 'actionButtonIcon2', 'icon', 'image', 'actionButtonUrl1', 'actionButtonUrl2'];

    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        $shouldRunMigration = false; // Please modify to your needs

        $columns = $schema->getTable($this->prefix.'push_notifications')->getColumns();
        foreach ($this->keys as $key) {
            if (!array_key_exists($key, $columns)) {
                $shouldRunMigration = true;
            }
        }
        if (!$shouldRunMigration) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $columns = $schema->getTable($this->prefix.'push_notifications')->getColumns();
        foreach ($this->keys as $key) {
            if (!array_key_exists($key, $columns)) {
                $this->addSql("ALTER TABLE {$this->prefix}push_notifications ADD {$key} VARCHAR(512) DEFAULT NULL;");
            }
        }
    }
}
