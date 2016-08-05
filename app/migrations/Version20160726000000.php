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
 * Class Version20160726000000
 */
class Version20160726000000 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        $table = $schema->getTable(MAUTIC_TABLE_PREFIX.'lead_frequencyrules');
        if ($table->hasColumn('channel')) {

            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $sql = <<<SQL
CREATE TABLE `{$this->prefix}lead_frequencyrules` (
  `id` int(11) NOT NULL,
  `lead_id` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  `channel` varchar(255) NOT NULL,
  `frequency_time` varchar(25) NOT NULL,
  `frequency_number` smallint(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;
SQL;
        $this->addSql($sql);
    }
}