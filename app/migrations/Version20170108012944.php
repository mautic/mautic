<?php
/**
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @see         http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170108012944 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("ALTER TABLE {$this->prefix}push_notifications CHANGE button button LONGTEXT NULL");

        $this->addSql("ALTER TABLE {$this->prefix}dynamic_content_lead_data DROP FOREIGN KEY ".$this->findPropertyName('dynamic_content_lead_data', 'fk', '6E55458D'));
        $this->addSql("ALTER TABLE {$this->prefix}dynamic_content_lead_data CHANGE lead_id lead_id INT NOT NULL");
        $this->addSql("ALTER TABLE {$this->prefix}dynamic_content_lead_data ADD CONSTRAINT ".$this->generatePropertyName('dynamic_content_lead_data', 'fk', ['lead_id'])." FOREIGN KEY (lead_id) REFERENCES {$this->prefix}leads (id) ON DELETE CASCADE");
    }
}
