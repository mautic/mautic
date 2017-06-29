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
class Version20170606094013 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        if ($schema->hasTable($this->prefix.'focus_campaign')) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $idx_c = $this->generatePropertyName('focus_campaign', 'idx', ['campaign_id']);
        $idx_f = $this->generatePropertyName('focus_campaign', 'idx', ['focus_id']);
        $idx_l = $this->generatePropertyName('focus_campaign', 'idx', ['lead_id']);

        $fk_c = $this->generatePropertyName('focus_campaign', 'fk', ['campaign_id']);
        $fk_f = $this->generatePropertyName('focus_campaign', 'fk', ['focus_id']);
        $fk_l = $this->generatePropertyName('focus_campaign', 'fk', ['lead_id']);

        $this->addSql('CREATE TABLE '.$this->prefix.'focus_campaign (id INT AUTO_INCREMENT NOT NULL, campaign_id INT DEFAULT NULL, focus_id INT DEFAULT NULL, lead_id INT DEFAULT NULL, INDEX '.$idx_c.' (campaign_id), INDEX '.$idx_f.' (focus_id), INDEX '.$idx_l.' (lead_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE '.$this->prefix.'focus_campaign ADD CONSTRAINT '.$fk_c.' FOREIGN KEY (campaign_id) REFERENCES campaigns (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE '.$this->prefix.'focus_campaign ADD CONSTRAINT '.$fk_f.' FOREIGN KEY (focus_id) REFERENCES focus (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE '.$this->prefix.'focus_campaign ADD CONSTRAINT '.$fk_l.' FOREIGN KEY (lead_id) REFERENCES leads (id) ON DELETE CASCADE');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE '.$this->prefix.'focus_campaign');
    }
}
