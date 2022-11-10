<?php

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190724110039 extends AbstractMauticMigration
{
    public function up(Schema $schema): void
    {
        // Change multiselect custom fields from VARCHAR(255) to TEXT
        $qb = $this->connection->createQueryBuilder();
        $qb->select('lf.alias')
           ->from($this->prefix.'lead_fields', 'lf')
           ->where(
               $qb->expr()->eq('lf.type', $qb->expr()->literal('multiselect'))
           );
        $multiselectFields = $qb->execute()->fetchAll();

        $leadsTable = $schema->getTable($this->prefix.'leads');

        if (!empty($multiselectFields)) {
            foreach ($multiselectFields as $field) {
                if ($leadsTable->hasColumn($field['alias'])) {
                    if ($leadsTable->hasIndex("{$this->prefix}{$field['alias']}_search")) {
                        $this->addSql("DROP INDEX `{$this->prefix}{$field['alias']}_search` ON `{$this->prefix}leads`");
                    }
                    $this->addSql("ALTER TABLE `{$this->prefix}leads` CHANGE `{$field['alias']}` `{$field['alias']}` TEXT");
                }
            }
        }
    }

    public function down(Schema $schema): void
    {
        // Change multiselect custom fields from VARCHAR(255) to TEXT
        $qb = $this->connection->createQueryBuilder();
        $qb->select('lf.alias')
           ->from($this->prefix.'lead_fields', 'lf')
           ->where(
               $qb->expr()->eq('lf.type', $qb->expr()->literal('multiselect'))
           );
        $multiselectFields = $qb->execute()->fetchAll();

        $leadsTable = $schema->getTable($this->prefix.'leads');

        if (!empty($multiselectFields)) {
            foreach ($multiselectFields as $field) {
                if ($leadsTable->hasColumn($field['alias'])) {
                    $this->addSql("ALTER TABLE `{$this->prefix}leads` CHANGE `{$field['alias']}` `{$field['alias']}` VARCHAR(255)");
                    if (!$leadsTable->hasIndex("{$this->prefix}{$field['alias']}_search")) {
                        $this->addSql("CREATE INDEX `{$this->prefix}{$field['alias']}_search` ON `{$this->prefix}leads`(`{$field['alias']}`)");
                    }
                }
            }
        }
    }
}
