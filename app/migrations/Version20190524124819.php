<?php

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;
use Mautic\LeadBundle\Field\Helper\IndexHelper;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190524124819 extends PreUpAssertionMigration
{
    protected function preUpAssertions(): void
    {
        $this->skipAssertion(function (Schema $schema) {
            return $schema->getTable("{$this->prefix}lead_fields")->hasColumn('is_index');
        }, sprintf('Schema includes this migration'));
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE {$this->prefix}lead_fields
	        ADD `is_index` TINYINT(1) DEFAULT 0 NOT NULL,
	        ADD `char_length_limit` INT(3) NULL;
        ");

        $this->addSql("
            UPDATE {$this->prefix}lead_fields
	        SET `char_length_limit` = 255 
	        WHERE `type` IN ('text', 'select', 'multiselect', 'phone', 'url', 'email')
	        AND `char_length_limit` IS NULL;
        ");

        $indexHelper    = $this->container->get(IndexHelper::class);
        $indexedColumns = implode("', '", $indexHelper->getIndexedColumnNames());

        $this->addSql("
            UPDATE {$this->prefix}lead_fields
	        SET `is_index` = TRUE 
	        WHERE `alias` IN ('{$indexedColumns}');
        ");
    }
}
