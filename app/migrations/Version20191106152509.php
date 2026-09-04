<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20191106152509 extends AbstractMauticMigration
{
    public function up(Schema $schema): void
    {
        // Skip if already applied (check if any non-string fields still have char_length_limit)
        if ($this->isAlreadyApplied()) {
            throw new SkipMigration('Migration already applied — no rows to update');
        }

        $tableName = $this->prefix.'lead_fields';

        // PostgreSQL: use double quotes if needed, but here identifiers are lowercase → no quotes required
        // MySQL: no backticks needed in this case either (but safe to keep quotes if mixed case)
        $sql = "
            UPDATE {$tableName}
            SET char_length_limit = NULL
            WHERE type NOT IN ('text', 'select', 'multiselect', 'phone', 'url', 'email')
              AND char_length_limit IS NOT NULL;
        ";

        $this->addSql($sql);
    }

    /**
     * Check if migration is already applied.
     */
    private function isAlreadyApplied(): bool
    {
        $sql = "
            SELECT COUNT(*)
            FROM {$this->prefix}lead_fields
            WHERE type NOT IN ('text', 'select', 'multiselect', 'phone', 'url', 'email')
              AND char_length_limit IS NOT NULL
        ";

        return 0 === (int) $this->connection->executeQuery($sql)->fetchOne();
    }

    public function down(Schema $schema): void
    {
        // Irreversible – we intentionally cleared limits; no sensible default to restore
        $this->throwIrreversibleMigrationException('Cannot reverse clearing of char_length_limit');
    }
}
