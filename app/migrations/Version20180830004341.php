<?php

/*
 * @package     Mautic
 * @copyright   2018 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Timezone was not added to custom fields on new installations when timezone was added as a core field.
 */
class Version20180830004341 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $qb      = $this->connection->createQueryBuilder();
        $results = $qb->select('*')
            ->from(MAUTIC_TABLE_PREFIX.'lead_fields')
            ->where('alias = "timezone"')
            ->execute();

        if ($results->rowCount()) {
            return;
        }

        $curDateTime = date('Y-m-d H:i:s');
        $sql         = <<<SQL
INSERT INTO `{$this->prefix}lead_fields` (`date_added`, `is_published`, `label`, `alias`, `type`, `field_group`, `default_value`, `is_required`, `is_fixed`, `is_visible`, `is_short_visible`, `is_listable`, `is_publicly_updatable`, `is_unique_identifer`, `field_order`, `properties`, `object`) 
VALUES 
  ('{$curDateTime}', 1,'Preferred Timezone', 'timezone', 'timezone', 'core', NULL , 0, 1, 1, 0, 1, 0, 0, 26, 'a:0:{}', 'lead')
SQL;
        $this->addSql($sql);
    }
}
