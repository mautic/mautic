<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\ScannerDiagnostic;

final class Version20261125100000 extends PreUpAssertionMigration
{
    protected const TABLE_NAME = ScannerDiagnostic::TABLE_NAME;

    public function getDescription(): string
    {
        return 'Store the requests made to email links and pixels while email scanner diagnostics is enabled';
    }

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => $schema->hasTable($this->getPrefixedTableName()),
            "Table {$this->getPrefixedTableName()} already exists"
        );
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable($this->getPrefixedTableName());
        $table->addColumn('id', Types::BIGINT, ['unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('email_id', Types::INTEGER, ['unsigned' => true, 'notnull' => false]);
        $table->addColumn('redirect_id', Types::BIGINT, ['unsigned' => true, 'notnull' => false]);
        $table->addColumn('date_added', Types::DATETIME_IMMUTABLE);
        $table->addColumn('event_type', Types::STRING, ['length' => 16]);
        $table->addColumn('send_key', Types::STRING, ['length' => 16, 'notnull' => false]);
        $table->addColumn('seconds_after_send', Types::INTEGER, ['notnull' => false]);
        $table->addColumn('ip_hash', Types::STRING, ['length' => 16]);
        $table->addColumn('ip_network', Types::STRING, ['length' => 43, 'notnull' => false]);
        $table->addColumn('user_agent', Types::STRING, ['length' => 255, 'notnull' => false]);
        $table->addColumn('headers', Types::JSON);
        $table->addColumn('is_https', Types::BOOLEAN);
        $table->addColumn('bot_ratio_flagged', Types::BOOLEAN, ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['email_id', 'send_key', 'date_added'], $this->prefix.ScannerDiagnostic::INDEX_EMAIL_SEND_DATE);
        $table->addIndex(['date_added'], $this->prefix.ScannerDiagnostic::INDEX_DATE_ADDED);

        // No names given, so Doctrine derives them from the prefixed table name the same way the entity mapping does.
        $table->addForeignKeyConstraint($this->getPrefixedTableName(Email::TABLE_NAME), ['email_id'], ['id'], ['onDelete' => 'CASCADE']);
        $table->addForeignKeyConstraint($this->getPrefixedTableName('page_redirects'), ['redirect_id'], ['id'], ['onDelete' => 'SET NULL']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable($this->getPrefixedTableName());
    }
}
