<?php

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
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema): void
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

    public function up(Schema $schema): void
    {
        $columns = $schema->getTable($this->prefix.'push_notifications')->getColumns();
        foreach ($this->keys as $key) {
            if (!array_key_exists($key, $columns)) {
                $this->addSql("ALTER TABLE {$this->prefix}push_notifications ADD {$key} VARCHAR(512) DEFAULT NULL;");
            }
        }
    }
}
