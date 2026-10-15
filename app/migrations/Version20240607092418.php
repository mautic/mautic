<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\BigIntType;
use Mautic\CoreBundle\Doctrine\DatabasePlatform;
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
        $this->changeIdType('BIGINT');
    }

    public function down(Schema $schema): void
    {
        $this->changeIdType('INT');
    }

    private function changeIdType(string $type): void
    {
        $table = $this->getPrefixedTableName(self::TABLE_NAME);
        if (DatabasePlatform::isPostgreSQL($this->platform)) {
            $this->addSql("ALTER TABLE {$table} ALTER COLUMN id TYPE {$type}");
            $sequence = DatabasePlatform::getSerialSequence($this->connection, $table);
            if ($sequence) {
                $this->addSql('ALTER SEQUENCE '.$this->connection->quoteIdentifier($sequence)." AS {$type}");
            }
        } else {
            $this->addSql("ALTER TABLE {$table} CHANGE id id {$type} UNSIGNED AUTO_INCREMENT NOT NULL");
        }
    }
}
