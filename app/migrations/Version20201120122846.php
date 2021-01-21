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
use Doctrine\DBAL\Schema\Table;
use Mautic\CampaignBundle\Entity\Summary;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20201120122846 extends PreUpAssertionMigration
{
    private const SIGNED   = 'SIGNED';
    private const UNSIGNED = 'UNSIGNED';

    protected function preUpAssertions(): void
    {
        $campaignSummaryTableName = $this->generateTableName(Summary::TABLE_NAME);
        $this->skipAssertion(
            function (Schema $schema) {
                return $schema->hasTable($this->generateTableName(Summary::TABLE_NAME));
            },
            sprintf('Schema already includes %s table', $campaignSummaryTableName)
        );
    }

    public function up(Schema $schema): void
    {
        $campaignIDX = $this->generatePropertyName(Summary::TABLE_NAME, 'idx', ['campaign_id']);
        $campaignFK  = $this->generatePropertyName(Summary::TABLE_NAME, 'fk', ['campaign_id']);
        $eventIDX    = $this->generatePropertyName(Summary::TABLE_NAME, 'idx', ['event_id']);
        $eventFK     = $this->generatePropertyName(Summary::TABLE_NAME, 'fk', ['event_id']);

        $campaignSummaryTableName = $this->generateTableName(Summary::TABLE_NAME);
        $campaignsTableName       = $this->generateTableName('campaigns');
        $campaignEventsTableName  = $this->generateTableName('campaign_events');

        $campaignIdDataType       = $this->getColumnDataType($schema->getTable($campaignsTableName), 'id');
        $campaignEventsIdDataType = $this->getColumnDataType($schema->getTable($campaignEventsTableName), 'id');

        $this->addSql("
            CREATE TABLE {$campaignSummaryTableName} (
                id INT UNSIGNED AUTO_INCREMENT NOT NULL,
                campaign_id INT {$campaignIdDataType} DEFAULT NULL,
                event_id INT {$campaignEventsIdDataType} NOT NULL,
                date_triggered DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                scheduled_count INT NOT NULL,
                triggered_count INT NOT NULL,
                non_action_path_taken_count INT NOT NULL,
                failed_count INT NOT NULL,
                log_counts_processed INT NOT NULL,
                INDEX {$campaignIDX} (campaign_id),
                INDEX {$eventIDX} (event_id),
                UNIQUE INDEX campaign_event_date_triggered (campaign_id, event_id, date_triggered),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC;
        ");

        $this->addSql("ALTER TABLE {$campaignSummaryTableName} ADD CONSTRAINT {$campaignFK} FOREIGN KEY (campaign_id) REFERENCES $campaignsTableName (id)");
        $this->addSql("ALTER TABLE {$campaignSummaryTableName} ADD CONSTRAINT {$eventFK} FOREIGN KEY (event_id) REFERENCES $campaignEventsTableName (id) ON DELETE CASCADE");
    }

    public function down(Schema $schema): void
    {
        $campaignSummaryTable = $this->generateTableName(Summary::TABLE_NAME);
        $this->addSql("DROP TABLE {$campaignSummaryTable}");
    }

    private function generateTableName(string $tableName): string
    {
        return "{$this->prefix}$tableName";
    }

    /**
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    private function getColumnDataType(Table $table, string $columnName): string
    {
        $column  = $table->getColumn($columnName);

        return $column->getUnsigned() ? self::UNSIGNED : self::SIGNED;
    }
}
