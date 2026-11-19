<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\BigIntType;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20240607092418 extends PreUpAssertionMigration
{
    protected const TABLE_NAME = 'webhook_queue';

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => $schema->getTable($this->getPrefixedTableName(self::TABLE_NAME))->getColumn('id')->getType() instanceof BigIntType,
            sprintf('Index %s already exists', 'index_name')
        );
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE {$this->getPrefixedTableName(self::TABLE_NAME)} CHANGE id id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE {$this->getPrefixedTableName(self::TABLE_NAME)} CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL");
    }
}
