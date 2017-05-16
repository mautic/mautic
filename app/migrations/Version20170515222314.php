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
class Version20170515222314 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        if ($schema->hasTable($this->prefix.'imports')) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $sql = <<<SQL
CREATE TABLE {$this->prefix}imports (
    id INT AUTO_INCREMENT NOT NULL,
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
    dir VARCHAR(255) NOT NULL,
    file VARCHAR(255) NOT NULL,
    original_file VARCHAR(255) DEFAULT NULL,
    line_count INT NOT NULL,
    inserted_count INT NOT NULL,
    updated_count INT NOT NULL,
    failed_count INT NOT NULL,
    priority INT NOT NULL,
    status INT NOT NULL,
    date_started DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
    date_ended DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
    object VARCHAR(255) NOT NULL,
    matchedFields LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json_array)',
    properties LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json_array)',
    INDEX {$this->prefix}import_object (object), INDEX import_status (status),
    INDEX {$this->prefix}import_priority (priority), PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;
SQL;

        $this->addSql($sql);
    }
}
