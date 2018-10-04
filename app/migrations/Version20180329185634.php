<?php

/*
 * @package     Mautic
 * @copyright   2018 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180329185634 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->connection->createQueryBuilder()
            ->delete($this->prefix.'lead_event_log')
            ->where('lead_id is null')
            ->execute();

        $fkName = $this->generatePropertyName('lead_event_log', 'fk', ['lead_id']);
        $this->addSql("ALTER TABLE {$this->prefix}lead_event_log DROP FOREIGN KEY $fkName");
        $this->addSql("ALTER TABLE {$this->prefix}lead_event_log ADD CONSTRAINT $fkName FOREIGN KEY (lead_id) REFERENCES {$this->prefix}leads (id) ON DELETE CASCADE");
    }
}
