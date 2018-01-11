<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Migrations\SkipMigrationException;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Class Version20180109075622.
 */
class Version20180109075622 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     */
    public function preUp(Schema $schema)
    {
        if ($schema->hasTable(MAUTIC_TABLE_PREFIX.'company_files')) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $sql = <<<SQL
        CREATE TABLE {$this->prefix}company_files (
            id INT AUTO_INCREMENT NOT NULL,
            company_id INT NOT NULL,
            title VARCHAR(255) DEFAULT NULL,
            path VARCHAR(255) DEFAULT NULL,
            original_file_name VARCHAR(255) DEFAULT NULL,
            mime VARCHAR(255) DEFAULT NULL,
            size INT DEFAULT NULL,
            INDEX {$this->prefix}company_files_company_id (company_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;
SQL;
        $this->addSql($sql);

        $this->addSql("ALTER TABLE  {$this->prefix}company_files ADD CONSTRAINT ".$this->generatePropertyName('company_files', 'fk', ['company_id']).' FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql("DROP TABLE  {$this->prefix}company_files");
    }
}
