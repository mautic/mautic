<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Migrations\SkipMigrationException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\StringType;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Change referer columns from varchar to longtext because of SQL error:
 * "Data too long for column 'referer'"
 * (GH issue #2187).
 */
class Version20161004080958 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        $table                   = $schema->getTable($this->prefix.'form_submissions');
        $submissionRefererColumn = $table->getColumn('referer');

        if (!($submissionRefererColumn->getType() instanceof StringType)) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql('ALTER TABLE '.$this->prefix.'asset_downloads CHANGE referer referer LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'form_submissions CHANGE referer referer LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'lead_utmtags CHANGE referer referer LONGTEXT DEFAULT NULL');
    }
}
