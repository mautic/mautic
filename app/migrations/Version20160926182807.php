<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Migrations\SkipMigrationException;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160926182807 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {

    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $sql        = <<<SQL
insert into {$this->prefix}companies (companyname) SELECT DISTINCT TRIM(company) from {$this->prefix}leads where company IS NOT NULL and company <> ''
SQL;

        $this->addSql($sql);

        $sql        = <<<SQL
insert into {$this->prefix}companies_leads (company_id, lead_id) SELECT c.id, l.id from {$this->prefix}leads l join {$this->prefix}companies c on c.companyname = l.company
SQL;

        $this->addSql($sql);

    }
}
