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
class Version20190906144117 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        if ($schema->hasTable($this->prefix.'point_tags')) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->abortIf(
            'mysql' != $this->connection->getDatabasePlatform()->getName(),
            'Migration can only be executed safely on \'mysql\'.'
        );

        $this->addSql(
            'CREATE TABLE '.$this->prefix.'point_tags_xref (point_id INT NOT NULL, tag_id INT NOT NULL, INDEX '.$this->generatePropertyName(
                'point_tags_xref',
                'idx',
                ['point_id']
            ).' (point_id), INDEX '.$this->generatePropertyName(
                'point_tags_xref',
                'idx',
                ['tag_id']
            ).' (tag_id), PRIMARY KEY(point_id, tag_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            'ALTER TABLE '.$this->prefix.'point_tags_xref ADD CONSTRAINT '.$this->generatePropertyName(
                'point_tags_xref',
                'fk',
                ['point_id']
            ).' FOREIGN KEY (point_id) REFERENCES '.$this->prefix.'points (id) ON DELETE CASCADE'
        );
        $this->addSql(
            'ALTER TABLE '.$this->prefix.'point_tags_xref ADD CONSTRAINT '.$this->generatePropertyName(
                'point_tags_xref',
                'fk',
                ['tag_id']
            ).' FOREIGN KEY (tag_id) REFERENCES '.$this->prefix.'lead_tags (id)'
        );
    }

    public function down(Schema $schema)
    {
        $this->abortIf(
            'mysql' != $this->connection->getDatabasePlatform()->getName(),
            'Migration can only be executed safely on \'mysql\'.'
        );

        $this->addSql('DROP TABLE '.$this->prefix.'point_tags_xref');
    }
}
