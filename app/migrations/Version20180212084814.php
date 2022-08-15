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
    private $keys = ['action_button_icon_1', 'action_button_icon_2', 'icon', 'image', 'action_button_url_1', 'action_button_url_2', 'action_button_text_2'];

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
                $this->addSql("ALTER TABLE {$this->prefix}push_notifications ADD {$key} VARCHAR(191) DEFAULT NULL;");
            }
        }
        $this->addSql("ALTER TABLE {$this->prefix}push_notifications ADD ttl INT NOT NULL");
        $this->addSql("ALTER TABLE {$this->prefix}push_notifications ADD priority INT NOT NULL");
    }
}
