<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;
use Mautic\UserBundle\Entity\UserCreationLock;

final class Version20261110120000 extends PreUpAssertionMigration
{
    public function getDescription(): string
    {
        return 'Serialize user creation to prevent concurrent username/email collisions';
    }

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => $schema->hasTable($this->getPrefixedTableName(UserCreationLock::TABLE_NAME)),
            'User creation lock table already exists'
        );
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable($this->getPrefixedTableName(UserCreationLock::TABLE_NAME));
        $table->addColumn('id', 'integer');
        $table->setPrimaryKey(['id']);
    }
}
