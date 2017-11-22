<?php

namespace Mautic\Migrations;

use Doctrine\DBAL\Migrations\SkipMigrationException;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171027134920 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     */
    public function preUp(Schema $schema)
    {
        if ($schema->hasTable(MAUTIC_TABLE_PREFIX.'reports_schedulers')) {
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

        $idx = $this->generatePropertyName('report_schedulers', 'idx', ['report_id']);
        $fk  = $this->generatePropertyName('report_schedulers', 'fk', ['report_id']);

        $this->addSql("CREATE TABLE {$this->prefix}reports_schedulers (id INT AUTO_INCREMENT NOT NULL, report_id INT NOT NULL, schedule_date DATETIME NOT NULL COMMENT '(DC2Type:datetime)', INDEX $idx (report_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("ALTER TABLE {$this->prefix}reports_schedulers ADD CONSTRAINT $fk FOREIGN KEY (report_id) REFERENCES {$this->prefix}reports (id) ON DELETE CASCADE");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DROP TABLE {$this->prefix}reports_schedulers");
    }
}
