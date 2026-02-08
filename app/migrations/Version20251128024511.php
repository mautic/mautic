<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20251128024511 extends AbstractMauticMigration
{
    private const TABLES_AND_COLUMNS = [
        'campaign_lead_event_log' => ['date_triggered'],
        'lead_donotcontact'       => ['date_added'],
        'lead_event_log'          => ['date_added'],
        'lead_points_change_log'  => ['date_added'],
        'lead_stages_change_log'  => ['date_added'],
        'lead_utmtags'            => ['date_added'],
        'email_stat_replies'      => ['date_replied'],
        'email_stats'             => ['date_sent', 'date_read', 'last_opened'],
        'email_stats_devices'     => ['date_opened'],
        'form_submissions'        => ['date_submitted'],
        'page_hits'               => ['date_hit', 'date_left'],
        'video_hits'              => ['date_hit', 'date_left'],
        'point_lead_action_log'   => ['date_fired'],
        'point_lead_event_log'    => ['date_fired'],
        'sms_message_stats'       => ['date_sent'],
        'stage_lead_action_log'   => ['date_fired'],
    ];

    private const NULLABLE_COLUMNS = [
        'date_triggered',
        'date_read',
        'last_opened',
        'date_left',
    ];

    public function up(Schema $schema): void
    {
        $this->alterColumns(true);
    }

    public function down(Schema $schema): void
    {
        $this->alterColumns(false);
    }

    private function alterColumns(bool $withMilliseconds): void
    {
        /** @var AbstractPlatform $platform */
        $platform = $this->connection->getDatabasePlatform();
        $isMysql  = $platform instanceof MySQLPlatform;

        $type = $withMilliseconds
            ? ($isMysql ? 'DATETIME(3)' : 'TIMESTAMP(3) WITHOUT TIME ZONE')
            : ($isMysql ? 'DATETIME' : 'TIMESTAMP(0) WITHOUT TIME ZONE');

        foreach (self::TABLES_AND_COLUMNS as $table => $columns) {
            $tableName = $this->getPrefixedTableName($table);

            foreach ($columns as $column) {
                if ($isMysql) {
                    $nullDef = in_array($column, self::NULLABLE_COLUMNS, true)
                        ? 'NULL DEFAULT NULL'
                        : 'NOT NULL';

                    $this->addSql(sprintf(
                        'ALTER TABLE %s MODIFY %s %s %s',
                        $tableName,
                        $column,
                        $type,
                        $nullDef
                    ));
                } else {
                    $this->addSql(sprintf(
                        'ALTER TABLE %s ALTER COLUMN %s TYPE %s',
                        $tableName,
                        $column,
                        $type
                    ));
                }
            }
        }
    }
}
