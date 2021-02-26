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
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20210217115150 extends PreUpAssertionMigration
{
    protected function preUpAssertions(): void
    {
        $this->skipAssertion(function (Schema $schema) {
            $table = $schema->getTable($this->getPrefixedTableName(LeadEventLog::TABLE_NAME));
            return !$table->hasForeignKey($this->getForeignKeyName('event_id'))
                && !$table->hasForeignKey($this->getForeignKeyName('campaign_id'));
        }, 'Migration already executed');
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable($this->getPrefixedTableName(LeadEventLog::TABLE_NAME));
        if ($table->hasForeignKey($this->getForeignKeyName('event_id'))) {
            $table->removeForeignKey($this->getForeignKeyName('event_id'));
        }
        if ($table->hasForeignKey($this->getForeignKeyName('campaign_id'))) {
            $table->removeForeignKey($this->getForeignKeyName('campaign_id'));
        }
    }

    private function getForeignKeyName(string $column): string
    {
        return $this->generatePropertyName(LeadEventLog::TABLE_NAME, 'fk', [$column]);
    }
}
