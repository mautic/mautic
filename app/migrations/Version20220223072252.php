<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
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
        $schema->getTable($this->getPrefixedTableName(self::TABLE))->modifyColumn('channel_id', [
            'type' => Type::getType(Types::STRING), 'length' => 64, 'notnull' => false, 'default' => null,
        ]);
    }
}
