<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;
use Mautic\EmailBundle\Entity\Email;

final class Version20240229101323 extends PreUpAssertionMigration
{
    private const COLUMN_NAME = 'send_to_dnc';

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(function (Schema $schema) {
            return $schema->getTable($this->getPrefixedTableName(Email::TABLE_NAME))->hasColumn(self::COLUMN_NAME);
        }, sprintf('Column %s already exists', self::COLUMN_NAME));
    }

    public function up(Schema $schema): void
    {
        $schema->getTable($this->getPrefixedTableName(Email::TABLE_NAME))->addColumn(self::COLUMN_NAME, Types::BOOLEAN, ['default' => false]);
    }

    public function down(Schema $schema): void
    {
        $schema->getTable($this->getPrefixedTableName(Email::TABLE_NAME))->dropColumn(self::COLUMN_NAME);
    }
}
