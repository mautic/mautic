<?php

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20190724110039 extends AbstractMauticMigration
{
    private const LEADS_TABLE       = 'leads';
    private const LEAD_FIELDS_TABLE = 'lead_fields';

    /**
     * Change multiselect custom fields from VARCHAR(191) to TEXT.
     */
    public function up(Schema $schema): void
    {
        $multiselectFields = $this->getMultiselectFields();
        $leadsTable        = $schema->getTable($this->prefix.self::LEADS_TABLE);
        $changeStatements  = [];

        if (!empty($multiselectFields)) {
            foreach ($multiselectFields as $field) {
                if ($leadsTable->hasColumn($field['alias'])) {
                    if ($leadsTable->hasIndex("{$this->prefix}{$field['alias']}_search")) {
                        $changeStatements[] = "DROP INDEX `{$this->prefix}{$field['alias']}_search`";
                    }
                    if (!empty($this->prefix) && $leadsTable->hasIndex("{$field['alias']}_search")) {
                        $changeStatements[] = "DROP INDEX `{$field['alias']}_search`";
                    }
                    $changeStatements[] = "CHANGE `{$field['alias']}` `{$field['alias']}` TEXT";
                }
            }

            if (!empty($changeStatements)) {
                $this->addSql('SET innodb_strict_mode = OFF;');
                $this->addSql('ALTER TABLE `'.$this->prefix.self::LEADS_TABLE.'` '.implode(', ', $changeStatements));
                $this->addSql('SET innodb_strict_mode = ON;');
            }
        }
    }

    /**
     * Change multiselect custom fields from VARCHAR(191) to TEXT.
     */
    public function down(Schema $schema): void
    {
        $multiselectFields = $this->getMultiselectFields();
        $leadsTable        = $schema->getTable($this->prefix.self::LEADS_TABLE);
        $changeStatements  = [];

        if (!empty($multiselectFields)) {
            foreach ($multiselectFields as $field) {
                if ($leadsTable->hasColumn($field['alias'])) {
                    $changeStatements[] = "CHANGE `{$field['alias']}` `{$field['alias']}` VARCHAR(191)";
                    if (!$leadsTable->hasIndex("{$this->prefix}{$field['alias']}_search")) {
                        $changeStatements[] = "ADD INDEX `{$this->prefix}{$field['alias']}_search` (`{$field['alias']}`)";
                    }
                }
            }

            if (!empty($changeStatements)) {
                $this->addSql('ALTER TABLE `'.$this->prefix.self::LEADS_TABLE.'` '.implode(', ', $changeStatements));
            }
        }
    }

    /**
     * @return array<array<string, mixed>>
     */
    private function getMultiselectFields(): array
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->select('lf.alias')
            ->from($this->prefix.self::LEAD_FIELDS_TABLE, 'lf')
            ->where(
                $qb->expr()->eq('lf.type', $qb->expr()->literal('multiselect'))
            );

        return $qb->execute()->fetchAll();
    }
}
