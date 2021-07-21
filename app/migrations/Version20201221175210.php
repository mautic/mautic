<?php

declare(strict_types=1);

/*
 * @copyright   <year> Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        https://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20201221175210 extends AbstractMauticMigration
{
    /**
     * @throws SkipMigrationException
     */
    public function preUp(Schema $schema): void
    {
        $shouldRunMigration = false; // Please modify to your needs

        if (!$shouldRunMigration) {
            throw new SkipMigration('Schema includes this migration');
        }
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX '.$this->prefix.'lead_donotcontact_idx_channel_lead_id ON '.$this->prefix.'lead_donotcontact (channel, lead_id)');
        $this->addSql('CREATE INDEX '.$this->prefix.'lead_lists_leads_idx_manually_lead_id_leadlist ON '.$this->prefix.'lead_lists_leads (manually_removed, lead_id, leadlist_id)');
        $this->addSql('CREATE INDEX '.$this->prefix.'message_queue_idx_channel_lead_id_status ON '.$this->prefix.'message_queue (channel, lead_id, status)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX '.$this->prefix.'lead_donotcontact_idx_channel_lead_id ON '.$this->prefix.'lead_donotcontact');
        $this->addSql('DROP INDEX '.$this->prefix.'lead_lists_leads_idx_manually_lead_id_leadlist ON '.$this->prefix.'lead_lists_leads');
        $this->addSql('DROP INDEX '.$this->prefix.'message_queue_idx_channel_lead_id_status ON '.$this->prefix.'message_queue');
    }
}
