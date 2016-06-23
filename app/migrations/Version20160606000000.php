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
 * Class Version20160606000000
 */
class Version20160606000000 extends AbstractMauticMigration
{
    private $keys = [];

    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        if ($schema->hasTable($this->prefix.'lead_attributions')) {

            throw new SkipMigrationException('Schema includes this migration');
        }

        $this->keys = [
            'idx' => [
                'lead'     => $this->generatePropertyName('lead_attributions', 'idx', ['lead_id']),
                'campaign' => $this->generatePropertyName('lead_attributions', 'idx', ['campaign_id']),
                'ip'       => $this->generatePropertyName('lead_attributions', 'idx', ['ip_id']),
                'stage'    => $this->generatePropertyName('lead_attributions', 'idx', ['stage_id']),
            ],
            'fk'  => [
                'lead'     => $this->generatePropertyName('lead_attributions', 'fk', ['lead_id']),
                'campaign' => $this->generatePropertyName('lead_attributions', 'fk', ['campaign_id']),
                'ip'       => $this->generatePropertyName('lead_attributions', 'fk', ['ip_id']),
                'stage'    => $this->generatePropertyName('lead_attributions', 'fk', ['stage_id']),
            ],
        ];
    }

    /**
     * @param Schema $schema
     */
    public function mysqlUp(Schema $schema)
    {
        $sql = <<<SQL
INSERT INTO `{$this->prefix}lead_fields` (`is_published`, `label`, `alias`, `type`, `field_group`, `default_value`, `is_required`, `is_fixed`, `is_visible`, `is_short_visible`, `is_listable`, `is_publicly_updatable`, `is_unique_identifer`, `field_order`, `properties`) 
VALUES 
  (1,'Attribution', 'attribution', 'number', 'core', '0', 0, 1, 1, 0, 1, 0, 0, 23, 'a:2:{s:9:\"roundmode\";s:1:\"4\";s:9:\"precision\";s:1:\"2\";}'),
  (1,'Attribution Date', 'attribution_date', 'date', 'core', '0', 0, 1, 1, 0, 1, 0, 0, 24, 'a:0:{}')
SQL;
        $this->addSql($sql);

        $this->addSql("ALTER TABLE {$this->prefix}leads ADD COLUMN attribution longtext");
        $this->addSql("ALTER TABLE {$this->prefix}leads ADD COLUMN attribution_date longtext");

    }

    /**
     * @param Schema $schema
     */
    public function postgresqlUp(Schema $schema)
    {

    }
}
