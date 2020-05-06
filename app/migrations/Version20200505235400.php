<?php

namespace Mautic\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20200505235400 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $table = $schema->getTable($this->prefix.'forms');
        if ($table->hasColumn('no_index')) {
            throw new SkipMigrationException('Schema includes this migration');
        }

        $this->addSql('ALTER TABLE '.$this->prefix.'forms ADD translation_parent_id INT DEFAULT NULL, ADD variant_parent_id INT DEFAULT NULL, ADD title VARCHAR(255) NOT NULL, ADD lang VARCHAR(255) NOT NULL, ADD variant_settings LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', ADD variant_start_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\', DROP no_index');
        $this->addSql('ALTER TABLE '.$this->prefix.'forms ADD CONSTRAINT FK_526285549091A2FB FOREIGN KEY (translation_parent_id) REFERENCES '.$this->prefix.'forms (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE '.$this->prefix.'forms ADD CONSTRAINT FK_5262855491861123 FOREIGN KEY (variant_parent_id) REFERENCES '.$this->prefix.'forms (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_526285549091A2FB ON '.$this->prefix.'forms (translation_parent_id)');
        $this->addSql('CREATE INDEX IDX_5262855491861123 ON '.$this->prefix.'forms (variant_parent_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE '.$this->prefix.'forms DROP FOREIGN KEY FK_526285549091A2FB');
        $this->addSql('ALTER TABLE '.$this->prefix.'forms DROP FOREIGN KEY FK_5262855491861123');
        $this->addSql('DROP INDEX IDX_526285549091A2FB ON '.$this->prefix.'forms');
        $this->addSql('DROP INDEX IDX_5262855491861123 ON '.$this->prefix.'forms');
        $this->addSql('ALTER TABLE '.$this->prefix.'forms ADD no_index TINYINT(1) DEFAULT NULL, DROP translation_parent_id, DROP variant_parent_id, DROP title, DROP lang, DROP variant_settings, DROP variant_start_date');
    }
}
