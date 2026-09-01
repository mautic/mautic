<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20250127092203 extends PreUpAssertionMigration
{
    protected const TABLE_NAME = 'user_invites';

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => $schema->hasTable($this->getPrefixedTableName(self::TABLE_NAME)),
            'Table '.self::TABLE_NAME.' already exists'
        );
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable($this->prefix.self::TABLE_NAME);
        $table->addColumn('id', Types::INTEGER, ['autoincrement' => true, 'unsigned' => true, 'notnull' => true]);
        $table->addColumn('email', Types::STRING, ['length' => 191, 'notnull' => true]);
        $table->addColumn('token_selector', Types::STRING, ['length' => 32, 'notnull' => true]);
        $table->addColumn('token_verifier_hash', Types::STRING, ['length' => 255, 'notnull' => true]);
        $table->addColumn('expiration', Types::DATETIME_MUTABLE, ['notnull' => true]);
        $table->addColumn('used', Types::BOOLEAN, ['notnull' => true]);
        $table->addColumn('role_id', Types::INTEGER, ['unsigned' => true, 'notnull' => true]);

        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['token_selector'], 'UNIQ_USER_INVITES_TOKEN_SELECTOR');
        $table->addIndex(['email'], 'IDX_USER_INVITES_EMAIL');
        $table->addIndex(['expiration'], 'IDX_USER_INVITES_EXPIRATION');
        $table->addIndex(['used'], 'IDX_USER_INVITES_USED');
        $table->addIndex(['role_id'], 'IDX_USER_INVITES_ROLE');
        $table->addForeignKeyConstraint(
            $this->getPrefixedTableName('roles'),
            ['role_id'],
            ['id'],
            ['onDelete' => 'CASCADE']
        );
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable($this->prefix.self::TABLE_NAME);
    }
}
