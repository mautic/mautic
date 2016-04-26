<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Migrations\SkipMigrationException;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Class Version20160405000000
 */

class Version20160405000000 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        if ($schema->hasTable($this->prefix.'widgets')) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function mysqlUp(Schema $schema)
    {
        $this->addSql('CREATE TABLE '.$this->prefix.'widgets (id INT AUTO_INCREMENT NOT NULL, is_published TINYINT(1) NOT NULL, date_added DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\', created_by INT DEFAULT NULL, created_by_user VARCHAR(255) DEFAULT NULL, date_modified DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\', modified_by INT DEFAULT NULL, modified_by_user VARCHAR(255) DEFAULT NULL, checked_out DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\', checked_out_by INT DEFAULT NULL, checked_out_by_user VARCHAR(255) DEFAULT NULL, name VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, width INT NOT NULL, height INT NOT NULL, cache_timeout INT DEFAULT NULL, ordering INT DEFAULT NULL, params LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;
');
    }

    /**
     * @param Schema $schema
     */
    public function postgresqlUp(Schema $schema)
    {
        $this->addSql('CREATE SEQUENCE '.$this->prefix.'widgets_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE '.$this->prefix.'widgets (id INT NOT NULL, is_published BOOLEAN NOT NULL, date_added TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_by INT DEFAULT NULL, created_by_user VARCHAR(255) DEFAULT NULL, date_modified TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, modified_by INT DEFAULT NULL, modified_by_user VARCHAR(255) DEFAULT NULL, checked_out TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, checked_out_by INT DEFAULT NULL, checked_out_by_user VARCHAR(255) DEFAULT NULL, name VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, width INT NOT NULL, height INT NOT NULL, cache_timeout INT DEFAULT NULL, ordering INT DEFAULT NULL, params TEXT DEFAULT NULL, PRIMARY KEY(id));
');
        $this->addSql('COMMENT ON COLUMN '.$this->prefix.'widgets.date_added IS \'(DC2Type:datetime)\'');
        $this->addSql('COMMENT ON COLUMN '.$this->prefix.'widgets.date_modified IS \'(DC2Type:datetime)\'');
        $this->addSql('COMMENT ON COLUMN '.$this->prefix.'widgets.checked_out IS \'(DC2Type:datetime)\'');
        $this->addSql('COMMENT ON COLUMN '.$this->prefix.'widgets.params IS \'(DC2Type:array)\'');
    }
}
