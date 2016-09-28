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
 * Class Version20160926000000
 */
class Version20160926000000 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        if ($schema->hasTable($this->prefix.'companies')) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $sql        = <<<SQL
CREATE TABLE {$this->prefix}companies (
  `id` int(11) AUTO_INCREMENT NOT NULL,
  `companyname` varchar(255) DEFAULT NULL,
  `companydescription` text DEFAULT NULL,
  `companyaddress1` varchar(255) DEFAULT NULL,
  `companyaddress2` varchar(255) DEFAULT NULL,
  `companycity` varchar(255) DEFAULT NULL,
  `companystate` varchar(255) DEFAULT NULL,
  `companyzipcode` varchar(11) DEFAULT NULL,
  `companycountry` varchar(255) DEFAULT NULL,
  `companyemail` varchar(255) DEFAULT NULL,
  `companyphone` varchar(50) DEFAULT NULL,
  `companyfax` varchar(50) DEFAULT NULL,
  `companyannual_revenue` float DEFAULT NULL,
  `companynumber_of_employees` int(11) DEFAULT NULL,
  `companywebsite` varchar(255) DEFAULT NULL,
  `companyindustry` varchar(255) DEFAULT NULL,
  `owner_id` int(11) DEFAULT NULL,
  `date_added` DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(255) DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(255) DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL COMMENT '(DC2Type:datetime)',
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(255) DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL COMMENT '(DC2Type:datetime)',
  `is_published` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY(id),
  INDEX {$this->prefix}companyname_search (companyname),
  INDEX {$this->prefix}companyaddress1_search (companyaddress1),
  INDEX {$this->prefix}companyaddress2_search (companyaddress2),
  INDEX {$this->prefix}companycity_search (companycity),
  INDEX {$this->prefix}companystate_search (companystate),
  INDEX {$this->prefix}companyzipcode_search (companyzipcode),
  INDEX {$this->prefix}companycountry_search (companycountry),
  INDEX {$this->prefix}companyemail_search (companyemail),
  INDEX {$this->prefix}companyphone_search (companyphone),
  INDEX {$this->prefix}companyfax_search (companyfax),
  INDEX {$this->prefix}companyannual_revenue_search (companyannual_revenue),
  INDEX {$this->prefix}companynumber_of_employees_search (companynumber_of_employees),
  INDEX {$this->prefix}companywebsite_search (companywebsite),
  INDEX {$this->prefix}companyindustry_search (companyindustry)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;
SQL;
        $this->addSql($sql);
        $lead_index = $this->generatePropertyName('companies_leads', 'idx', ['lead_id']);
        $company_index =  $this->generatePropertyName('companies_leads', 'idx', ['company_id']);

        $sql = <<<SQL
CREATE TABLE {$this->prefix}companies_leads (
        lead_id INT NOT NULL, 
        company_id INT NOT NULL, 
        date_added DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
        manually_added TINYINT(1) DEFAULT 0,
        manually_removed TINYINT(1) DEFAULT 0,
        INDEX {$lead_index} (lead_id), 
        INDEX {$company_index} (company_id),
        PRIMARY KEY(lead_id, company_id)
        ) 
        DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB
SQL;

        $this->addSql($sql);

        $lead_fk = $this->generatePropertyName('companies_leads', 'fk', ['lead_id']);
        $company_fk = $this->generatePropertyName('companies_leads', 'fk', ['company_id']);

        $this->addSql("ALTER TABLE {$this->prefix}companies_leads ADD CONSTRAINT {$company_fk} FOREIGN KEY (company_id) REFERENCES {$this->prefix}companies (id) ON DELETE CASCADE;");
        $this->addSql("ALTER TABLE {$this->prefix}companies_leads ADD CONSTRAINT {$lead_fk} FOREIGN KEY (lead_id) REFERENCES {$this->prefix}leads (id) ON DELETE CASCADE;");

        $this->addSql("ALTER TABLE {$this->prefix}lead_fields ADD object VARCHAR(255) DEFAULT 'lead'");

        $sql = <<<SQL
INSERT INTO `{$this->prefix}lead_fields` (`is_published`, `label`, `alias`, `type`, `field_group`, `default_value`, `is_required`, `is_fixed`, `is_visible`, `is_short_visible`, `is_listable`, `is_publicly_updatable`, `is_unique_identifer`, `field_order`, `object`,`properties`) 
VALUES 
(1, 'Name', 'companyname', 'text', 'core', NULL, 1, 0, 1, 1, 1, 0, 0, 18, 'company', 'a:0:{}'),
(1, 'Description', 'companydescription', 'textarea', 'professional', NULL, 0, 0, 1, 1, 1, 0, 0, 17, 'company', 'a:0:{}'),
(1, 'Address 1', 'companyaddress1', 'text', 'core', NULL, 0, 0, 1, 1, 1, 0, 0, 13, 'company', 'a:0:{}'),
(1, 'Address 2', 'companyaddress2', 'text', 'core', NULL, 0, 0, 1, 1, 1, 0, 0, 12, 'company', 'a:0:{}'),
(1, 'Company Email', 'companyemail', 'email', 'core', NULL, 0, 0, 1, 1, 1, 0, 1, 11, 'company', 'a:0:{}'),
(1, 'Phone', 'companyphone', 'tel', 'core', NULL, 0, 0, 1, 1, 1, 0, 0, 10, 'company', 'a:0:{}'),
(1, 'City', 'companycity', 'text', 'core', NULL, 0, 0, 1, 1, 1, 0, 1, 9, 'company', 'a:0:{}'),
(1, 'State', 'companystate', 'text', 'core', NULL, 0, 0, 1, 1, 1, 0, 0, 8, 'company', 'a:0:{}'),
(1, 'Zip Code', 'companyzipcode', 'text', 'core', NULL, 0, 0, 1, 1, 1, 0, 0, 7, 'company', 'a:0:{}'),
(1, 'Country', 'companycountry', 'country', 'core', NULL, 0, 0, 1, 1, 1, 0, 1, 6, 'company', 'a:0:{}'),
(1, 'Number of Employees', 'companynumber_of_employees', 'number', 'professional', NULL, 0, 0, 1, 1, 1, 0, 0, 5, 'company', 'a:2:{s:9:"roundmode";s:1:"3";s:9:"precision";s:1:"0";}'),
(1, 'Fax', 'companyfax', 'tel', 'professional', NULL, 0, 0, 1, 1, 1, 0, 0, 4, 'company', 'a:0:{}'),
(1, 'Annual Revenue', 'companyannual_revenue', 'number', 'professional', NULL, 0, 0, 1, 1, 1, 0, 1, 2, 'company', 'a:2:{s:9:"roundmode";s:1:"3";s:9:"precision";s:1:"2";}'),
(1, 'Website', 'companywebsite', 'url', 'core', NULL, 0, 0, 1, 1, 1, 0, 0, 1, 'company', 'a:0:{}');
(1, 'Industry', 'companyindustry', 'lookup', 'professional', NULL, 0, 0, 1, 1, 1, 0, 0, 14, 'company', 'a:1:{s:4:"list";s:55:"Agriculture|Apparel|Banking|Biotechnology|Chemicals|Communications|Construction|Education|Electronics|Energy|Engineering|Entertainment|Environmental|Finance|Food & Beverage|Government|Healthcare|Hospitality|Insurance|Machinery|Manufacturing|Media|Not for Profit|Recreation|Retail|Shipping|Technology|Telecommunications|Transportation|Utilities|Other";}')
SQL;

        $this->addSql($sql);
    }
}
