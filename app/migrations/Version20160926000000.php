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
  `id` int(11) NOT NULL,
  `company_number` varchar(255) DEFAULT NULL,
  `company_source` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `address1` varchar(255) DEFAULT NULL,
  `address2` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `zipcode` varchar(11) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `fax` varchar(50) DEFAULT NULL,
  `annual_revenue` float DEFAULT NULL,
  `number_of_employees` int(11) DEFAULT NULL,
  `score` int(11) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
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
  `publish_up` datetime DEFAULT NULL COMMENT '(DC2Type:datetime)',
  `publish_down` datetime DEFAULT NULL COMMENT '(DC2Type:datetime)',
  `is_published` tinyint(4) DEFAULT NULL,
  PRIMARY KEY(id),
  INDEX {$this->prefix}comany_name (`name`, `email`)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;
SQL;
        $this->addSql($sql);
        $lead_index = $this->generatePropertyName('company_leads_xref', 'idx', array('lead_id'));
        $company_index =  $this->generatePropertyName('company_leads_xref', 'idx', array('company_id'));
        $sql = <<<SQL
CREATE TABLE {$this->prefix}company_leads_xref (
        lead_id INT NOT NULL, 
        company_id INT NOT NULL, 
        INDEX {$lead_index} (lead_id), 
        INDEX {$company_index} (company_id), 
        PRIMARY KEY(lead_id, company_id)
        ) 
        DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB
SQL;

        $this->addSql($sql);
    }
}