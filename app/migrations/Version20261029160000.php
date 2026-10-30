<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;
use Mautic\EmailBundle\Entity\Email;

final class Version20261029160000 extends PreUpAssertionMigration
{
    private const COLUMN_NAME = 'tracking_enabled';

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(fn (Schema $schema) => $schema->getTable($this->getPrefixedTableName(Email::TABLE_NAME))->hasColumn(self::COLUMN_NAME), sprintf('Column %s already exists', self::COLUMN_NAME));
    }

    public function getDescription(): string
    {
        return 'Let each email turn tracking of opens and clicks off';
    }

    public function up(Schema $schema): void
    {
        // Existing emails keep tracking, as before
        $schema->getTable($this->getPrefixedTableName(Email::TABLE_NAME))->addColumn(self::COLUMN_NAME, Types::BOOLEAN, ['default' => true]);
    }

    public function down(Schema $schema): void
    {
        $schema->getTable($this->getPrefixedTableName(Email::TABLE_NAME))->dropColumn(self::COLUMN_NAME);
    }
}
