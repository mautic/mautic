<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190724110039 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
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
            foreach ($multiselectFields as $key => $field) {
                if ($leadsTable->hasColumn($field['alias'])) {
                    if ($leadsTable->hasIndex("{$field['alias']}_search")) {
                        $this->addSql("DROP INDEX `{$field['alias']}_search` ON `{$this->prefix}leads`");
                    }
                    $this->addSql("ALTER TABLE `{$this->prefix}leads` CHANGE `{$field['alias']}` `{$field['alias']}` TEXT");
                }
            }
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
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
            foreach ($multiselectFields as $key => $field) {
                if ($leadsTable->hasColumn($field['alias'])) {
                    $this->addSql("ALTER TABLE `{$this->prefix}leads` CHANGE `{$field['alias']}` `{$field['alias']}` VARCHAR(255)");
                    if (!$leadsTable->hasIndex("{$field['alias']}_search")) {
                        $this->addSql("CREATE INDEX `{$field['alias']}_search` ON `{$this->prefix}leads`(`{$field['alias']}`)");
                    }
                }
            }
        }
    }
}
