<?php

namespace Mautic\Migrations;

use Doctrine\DBAL\Migrations\SkipMigrationException;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Migration 20170908063216. Adds tables for the deal handling feature added
 * to the pipedrive integration.
 */
class Version20170908063216 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        if ($schema->hasTable($this->prefix.'plugin_crm_pipedrive_deals')) {
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

        $this->addSql('CREATE TABLE plugin_crm_pipedrive_deal_product (id INT AUTO_INCREMENT NOT NULL, deal_id INT NOT NULL, product_id INT NOT NULL, item_price INT NOT NULL, quantity INT NOT NULL, INDEX IDX_3E84A20FF60E2305 (deal_id), INDEX IDX_3E84A20F4584665A (product_id), UNIQUE INDEX unique_deal_product (deal_id, product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE plugin_crm_pipedrive_pipelines (id INT AUTO_INCREMENT NOT NULL, pipeline_id INT NOT NULL, name VARCHAR(255) NOT NULL, active TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE plugin_crm_pipedrive_products (id INT AUTO_INCREMENT NOT NULL, product_id INT NOT NULL, name VARCHAR(255) NOT NULL, active TINYINT(1) NOT NULL, selectable TINYINT(1) NOT NULL, UNIQUE INDEX unique_product (product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE plugin_crm_pipedrive_stages (id INT AUTO_INCREMENT NOT NULL, pipeline_id INT NOT NULL, stage_id INT NOT NULL, name VARCHAR(255) NOT NULL, active TINYINT(1) NOT NULL, order_nr INT NOT NULL, INDEX IDX_9A4CEECEE80B93 (pipeline_id), UNIQUE INDEX unique_stage (stage_id), UNIQUE INDEX unique_pipeline_stage (stage_id, pipeline_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE plugin_crm_pipedrive_deals (id INT AUTO_INCREMENT NOT NULL, lead_id INT NOT NULL, stage_id INT NOT NULL, deal_id INT NOT NULL, title VARCHAR(255) NOT NULL, INDEX IDX_D1721F2655458D (lead_id), INDEX IDX_D1721F262298D193 (stage_id), UNIQUE INDEX unique_stage (deal_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE plugin_crm_pipedrive_deal_product ADD CONSTRAINT FK_3E84A20FF60E2305 FOREIGN KEY (deal_id) REFERENCES plugin_crm_pipedrive_deals (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE plugin_crm_pipedrive_deal_product ADD CONSTRAINT FK_3E84A20F4584665A FOREIGN KEY (product_id) REFERENCES plugin_crm_pipedrive_products (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE plugin_crm_pipedrive_stages ADD CONSTRAINT FK_9A4CEECEE80B93 FOREIGN KEY (pipeline_id) REFERENCES plugin_crm_pipedrive_pipelines (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE plugin_crm_pipedrive_deals ADD CONSTRAINT FK_D1721F2655458D FOREIGN KEY (lead_id) REFERENCES leads (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE plugin_crm_pipedrive_deals ADD CONSTRAINT FK_D1721F262298D193 FOREIGN KEY (stage_id) REFERENCES plugin_crm_pipedrive_stages (id) ON DELETE CASCADE');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE plugin_crm_pipedrive_stages DROP FOREIGN KEY FK_9A4CEECEE80B93');
        $this->addSql('ALTER TABLE plugin_crm_pipedrive_deal_product DROP FOREIGN KEY FK_3E84A20F4584665A');
        $this->addSql('ALTER TABLE plugin_crm_pipedrive_deals DROP FOREIGN KEY FK_D1721F262298D193');
        $this->addSql('ALTER TABLE plugin_crm_pipedrive_deal_product DROP FOREIGN KEY FK_3E84A20FF60E2305');
        $this->addSql('DROP TABLE plugin_crm_pipedrive_deal_product');
        $this->addSql('DROP TABLE plugin_crm_pipedrive_pipelines');
        $this->addSql('DROP TABLE plugin_crm_pipedrive_products');
        $this->addSql('DROP TABLE plugin_crm_pipedrive_stages');
        $this->addSql('DROP TABLE plugin_crm_pipedrive_deals');
    }
}
