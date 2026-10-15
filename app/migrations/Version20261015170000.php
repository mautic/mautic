<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\CoreBundle\Doctrine\DatabasePlatform;

final class Version20261015170000 extends AbstractMauticMigration
{
    public function getDescription(): string
    {
        return 'Align legacy PostgreSQL index and foreign key names with prefixed entity metadata';
    }

    public function up(Schema $schema): void
    {
        $this->skipIf(!DatabasePlatform::isPostgreSQL($this->platform), 'Only PostgreSQL needs legacy name reconciliation.');

        $inviteIndexes = [];
        foreach (['UNIQ_USER_INVITES_TOKEN_SELECTOR', 'IDX_USER_INVITES_EMAIL', 'IDX_USER_INVITES_EXPIRATION', 'IDX_USER_INVITES_USED', 'IDX_USER_INVITES_ROLE'] as $name) {
            $inviteIndexes[$name] = $this->prefix.$name;
        }
        $tables = [
            'user_invites' => [$inviteIndexes, []],
            'sms_messages' => [
                ['IDX_SMS_TRANSLATION_PARENT' => $this->generatePropertyName('sms_messages', 'idx', ['translation_parent_id'])],
                ['FK_SMS_TRANSLATION_PARENT' => $this->generatePropertyName('sms_messages', 'fk', ['translation_parent_id'])],
            ],
            'push_notifications' => [
                ['IDX_PUSH_NOTIFICATIONS_TRANSLATION_PARENT' => $this->generatePropertyName('push_notifications', 'idx', ['translation_parent_id'])],
                ['FK_PUSH_NOTIFICATIONS_TRANSLATION_PARENT' => $this->generatePropertyName('push_notifications', 'fk', ['translation_parent_id'])],
            ],
            'point_insights' => [
                ['IDX_9894AA2612469DE2' => $this->generatePropertyName('point_insights', 'idx', ['category_id'])],
                ['FK_9894AA2612469DE2' => $this->generatePropertyName('point_insights', 'fk', ['category_id'])],
            ],
        ];
        $manager = $this->connection->createSchemaManager();
        foreach ($tables as $name => [$indexRenames, $foreignKeyRenames]) {
            $tableName = $this->getPrefixedTableName($name);
            if (!$schema->hasTable($tableName)) {
                continue;
            }

            $indexes = $manager->listTableIndexes($tableName);
            foreach ($indexRenames as $old => $new) {
                if (isset($indexes[strtolower($old)]) && !isset($indexes[strtolower($new)])) {
                    $this->addSql('ALTER INDEX '.$this->connection->quoteIdentifier($indexes[strtolower($old)]->getName()).' RENAME TO '.$this->connection->quoteIdentifier(strtolower($new)));
                }
            }

            $table = $schema->getTable($tableName);
            foreach ($foreignKeyRenames as $old => $new) {
                if ($table->hasForeignKey($old) && !$table->hasForeignKey($new)) {
                    $this->addSql('ALTER TABLE '.$this->connection->quoteIdentifier($tableName).' RENAME CONSTRAINT '.$this->connection->quoteIdentifier($table->getForeignKey($old)->getName()).' TO '.$this->connection->quoteIdentifier(strtolower($new)));
                }
            }
        }
        $this->skipIf([] === $this->getSql(), 'No legacy PostgreSQL index or constraint names remain.');
    }
}
