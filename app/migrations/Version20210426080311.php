<?php

declare(strict_types=1);

/*
 * @copyright   2021 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        https://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\AssetBundle\Entity\Download;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Entity\StatDevice;
use Mautic\FormBundle\Entity\Submission;
use Mautic\LeadBundle\Entity\PointsChangeLog;
use Mautic\NotificationBundle\Entity\Stat as NotificationStat;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Entity\VideoHit;
use Mautic\PointBundle\Entity\LeadPointLog;
use Mautic\PointBundle\Entity\LeadTriggerLog;
use Mautic\SmsBundle\Entity\Stat as SmsStat;
use Mautic\StageBundle\Entity\LeadStageLog;

final class Version20210426080311 extends PreUpAssertionMigration
{
    const COLUMN_NAME            = 'ip_id';
    const PRIMARY_ID_COLUMN_NAME = 'id';
    private $associated_tables   = [
        Download::TABLE_NAME         => false,
        LeadEventLog::TABLE_NAME     => true,
        StatDevice::TABLE_NAME       => true,
        STAT::TABLE_NAME             => true,
        Submission::TABLE_NAME       => false,
        'lead_ips_xref'              => true,
        PointsChangeLog::TABLE_NAME  => false,
        NotificationStat::TABLE_NAME => true,
        VideoHit::TABLE_NAME         => false,
        LeadTriggerLog::TABLE_NAME   => true,
        LeadPointLog::TABLE_NAME     => true,
        SmsStat::TABLE_NAME          => true,
        LeadStageLog::TABLE_NAME     => true,
        Hit::TABLE_NAME              => false,
    ];

    protected function preUpAssertions(): void
    {
        foreach ($this->associated_tables as $tableName => $allowNull) {
            $this->skipAssertion(function (Schema $schema) use ($tableName) {
                return $this->isChangesExecuted($schema, $tableName);
            }, sprintf('On delete %s already updated for foreign key %s in table %s', $this->getOnDeleteValue($tableName), $this->getForeignKeyName($tableName), $tableName));

            if (!$allowNull) {
                $this->skipAssertion(function (Schema $schema) use ($tableName) {
                    $table = $schema->getTable($this->getPrefixedTableName($tableName));

                    return !$table->getColumn(self::COLUMN_NAME)->getNotnull();
                }, sprintf('allow null already updated for column %s in table %s', self::COLUMN_NAME, $tableName));
            }
        }
    }

    public function up(Schema $schema): void
    {
        foreach ($this->associated_tables as $tableName => $allowNull) {
            $table = $schema->getTable($this->getPrefixedTableName($tableName));
            $table->removeForeignKey($this->getForeignKeyName($tableName));
            $table->addForeignKeyConstraint($this->getPrefixedTableName(IpAddress::TABLE_NAME),
                [self::COLUMN_NAME],
                [self::PRIMARY_ID_COLUMN_NAME],
                ['onDelete' => $this->getOnDeleteValue($tableName)],
                $this->getForeignKeyName($tableName)
            );

            if (!$allowNull) {
                $table->getColumn(self::COLUMN_NAME)->setNotnull(false);
            }
        }
    }

    private function getOnDeleteValue(string $tableName): string
    {
        return 'lead_ips_xref' === $tableName ? 'CASCADE' : 'SET NULL';
    }

    private function isChangesExecuted(Schema $schema, string $tableName): bool
    {
        $table = $schema->getTable($this->getPrefixedTableName($tableName));

        return $table->getForeignKey($this->getForeignKeyName($tableName))->onDelete() === $this->getOnDeleteValue($tableName);
    }

    private function getForeignKeyName(string $tableName): string
    {
        return $this->generatePropertyName($tableName, 'fk', [self::COLUMN_NAME]);
    }
}
