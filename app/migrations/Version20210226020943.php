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
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20210226020943 extends PreUpAssertionMigration
{
    protected function preUpAssertions(): void
    {
        $this->skipAssertion(function (Schema $schema) {
            return !$schema->getTable($this->getPrefixedTableName(Campaign::TABLE_NAME))
                ->hasForeignKey($this->getForeignKeyName('campaign_id'));
        }, sprintf('Foreign key %s already removed', $this->getForeignKeyName('campaign_id')));
    }

    public function up(Schema $schema): void
    {
        $schema->getTable($this->getPrefixedTableName(Campaign::TABLE_NAME))
            ->removeForeignKey($this->getForeignKeyName('campaign_id'));
    }

    private function getForeignKeyName(string $column): string
    {
        return $this->generatePropertyName(Campaign::TABLE_NAME, 'fk', [$column]);
    }
}
