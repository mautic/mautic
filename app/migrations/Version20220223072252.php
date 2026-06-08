<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\StringType;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20220223072252 extends PreUpAssertionMigration
{
    private const TABLE = 'campaign_events';

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(function (Schema $schema) {
            return $schema->getTable($this->getPrefixedTableName(self::TABLE))->getColumn('channel_id')->getType() instanceof StringType;
        }, 'Column already in Varchar type');
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `{$this->getPrefixedTableName(self::TABLE)}` MODIFY `channel_id` VARCHAR(64) DEFAULT NULL;");
    }
}
