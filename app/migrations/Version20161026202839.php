<?php
/**
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @see         http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Migrations\SkipMigrationException;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161026202839 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        if ($schema->hasTable(MAUTIC_TABLE_PREFIX.'messages')) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $messageIdx = $this->generatePropertyName('message_channels', 'idx', ['message_id']);
        $sql        = <<<SQL
CREATE TABLE {$this->prefix}message_channels (
  id INT AUTO_INCREMENT NOT NULL, 
  message_id INT NOT NULL, 
  channel VARCHAR(255) NOT NULL, 
  channel_id INT NULL,
  properties LONGTEXT NOT NULL COMMENT '(DC2Type:json_array)',
  is_enabled TINYINT(1) NOT NULL,
  INDEX $messageIdx (message_id),  
  INDEX {$this->prefix}channel_entity_index (channel, channel_id),
  INDEX {$this->prefix}channel_enabled_index (channel, is_enabled),
  UNIQUE INDEX {$this->prefix}channel_index (message_id, channel),
  PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;
SQL;
        $this->addSql($sql);

        $categoryIdx = $this->generatePropertyName('messages', 'idx', ['category_id']);
        $sql         = <<<SQL
CREATE TABLE {$this->prefix}messages (
    id INT AUTO_INCREMENT NOT NULL, 
    category_id INT DEFAULT NULL, 
    is_published TINYINT(1) NOT NULL, 
    date_added DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)', 
    created_by INT DEFAULT NULL, 
    created_by_user VARCHAR(255) DEFAULT NULL, 
    date_modified DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)', 
    modified_by INT DEFAULT NULL, 
    modified_by_user VARCHAR(255) DEFAULT NULL, 
    checked_out DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)', 
    checked_out_by INT DEFAULT NULL, 
    checked_out_by_user VARCHAR(255) DEFAULT NULL, 
    name VARCHAR(255) NOT NULL, 
    description LONGTEXT DEFAULT NULL, 
    publish_up DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)', 
    publish_down DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)', 
    INDEX $categoryIdx (category_id), 
    INDEX {$this->prefix}date_message_added (date_added), 
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;
SQL;
        $this->addSql($sql);

        // Foreign key constraints
        $messageFk = $this->generatePropertyName('message_channels', 'fk', ['message_id']);
        $this->addSql("ALTER TABLE {$this->prefix}message_channels ADD CONSTRAINT $messageFk FOREIGN KEY (message_id) REFERENCES {$this->prefix}messages (id) ON DELETE CASCADE");

        $categoryFk = $this->generatePropertyName('messages', 'fk', ['category_id']);
        $this->addSql("ALTER TABLE {$this->prefix}messages ADD CONSTRAINT $categoryFk FOREIGN KEY (category_id) REFERENCES {$this->prefix}categories (id) ON DELETE SET NULL");
    }
}
