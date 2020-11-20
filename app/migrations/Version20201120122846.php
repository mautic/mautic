<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        https://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CampaignBundle\Entity\Summary;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20201120122846 extends AbstractMauticMigration
{
    /**
     * @throws SkipMigration
     */
    public function preUp(Schema $schema): void
    {
        if ($schema->hasTable($this->getTableName())) {
            throw new SkipMigration('Schema includes this migration');
        }
    }

    public function up(Schema $schema): void
    {
        $campaignIDX = $this->generatePropertyName(Summary::TABLE_NAME, 'idx', ['campaign_id']);
        $campaignFK  = $this->generatePropertyName(Summary::TABLE_NAME, 'fk', ['campaign_id']);
        $eventIDX    = $this->generatePropertyName(Summary::TABLE_NAME, 'idx', ['evemt_id']);
        $eventFK     = $this->generatePropertyName(Summary::TABLE_NAME, 'fk', ['event_id']);

        $this->addSql("
            CREATE TABLE {$this->getTableName()} (
                id INT UNSIGNED AUTO_INCREMENT NOT NULL,
                campaign_id INT UNSIGNED DEFAULT NULL,
                event_id INT UNSIGNED NOT NULL,
                date_triggered DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                scheduled_count INT NOT NULL,
                triggered_count INT NOT NULL,
                non_action_path_taken_count INT NOT NULL,
                failed_count INT NOT NULL,
                INDEX {$campaignIDX} (campaign_id),
                INDEX {$eventIDX} (event_id),
                UNIQUE INDEX campaign_event_date_triggered (campaign_id, event_id, date_triggered),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC;
        ");

        $this->addSql("ALTER TABLE {$this->getTableName()} ADD CONSTRAINT {$campaignFK} FOREIGN KEY (campaign_id) REFERENCES campaigns (id)");
        $this->addSql("ALTER TABLE {$this->getTableName()} ADD CONSTRAINT {$eventFK} FOREIGN KEY (event_id) REFERENCES campaign_events (id) ON DELETE CASCADE");
    }

    private function getTableName(): string
    {
        return $this->prefix.Summary::TABLE_NAME;
    }
}
