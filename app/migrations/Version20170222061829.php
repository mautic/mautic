<?php

namespace Mautic\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170222061829 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE dynamic_content_lead_data DROP FOREIGN KEY FK_515B221BD9D0CD7');
        $this->addSql('ALTER TABLE dynamic_content_lead_data ADD CONSTRAINT FK_515B221BD9D0CD7 FOREIGN KEY (dynamic_content_id) REFERENCES dynamic_content (id) ON DELETE CASCADE');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE dynamic_content_lead_data DROP FOREIGN KEY FK_515B221BD9D0CD7');
        $this->addSql('ALTER TABLE dynamic_content_lead_data ADD CONSTRAINT FK_515B221BD9D0CD7 FOREIGN KEY (dynamic_content_id) REFERENCES dynamic_content (id)');
    }
}
