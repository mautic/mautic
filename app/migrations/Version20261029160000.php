<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;

final class Version20261029160000 extends PreUpAssertionMigration
{
    private const COLUMN_NAME = 'tracking_enabled';

    protected function preUpAssertions(): void
    {
        foreach ([Email::TABLE_NAME, Stat::TABLE_NAME] as $tableName) {
            $this->skipAssertion(
                fn (Schema $schema) => $schema->getTable($this->getPrefixedTableName($tableName))->hasColumn(self::COLUMN_NAME),
                sprintf('Column %s.%s already exists', $tableName, self::COLUMN_NAME)
            );
        }
    }

    public function getDescription(): string
    {
        return 'Let each email turn tracking of opens and clicks off';
    }

    public function up(Schema $schema): void
    {
        // Existing emails keep tracking, as before. Each sent copy keeps the email's choice from when it was sent.
        foreach ([Email::TABLE_NAME, Stat::TABLE_NAME] as $tableName) {
            $table = $schema->getTable($this->getPrefixedTableName($tableName));
            if (!$table->hasColumn(self::COLUMN_NAME)) {
                $table->addColumn(self::COLUMN_NAME, Types::BOOLEAN, ['default' => true]);
            }
        }
    }

    public function down(Schema $schema): void
    {
        foreach ([Email::TABLE_NAME, Stat::TABLE_NAME] as $tableName) {
            $schema->getTable($this->getPrefixedTableName($tableName))->dropColumn(self::COLUMN_NAME);
        }
    }
}
