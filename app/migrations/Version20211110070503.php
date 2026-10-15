<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\BooleanType;
use Mautic\CoreBundle\Doctrine\DatabasePlatform;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20211110070503 extends PreUpAssertionMigration
{
    public function preUpAssertions(): void
    {
        $this->skipAssertion(function (Schema $schema) {
            return $schema->getTable("{$this->prefix}campaigns")->getColumn('allow_restart')->getType() instanceof BooleanType;
        }, 'Column already in BOOLEAN type');
    }

    public function up(Schema $schema): void
    {
        if (DatabasePlatform::isPostgreSQL($this->platform)) {
            $this->addSql("ALTER TABLE {$this->prefix}campaigns ALTER COLUMN allow_restart DROP DEFAULT");
            $this->addSql("ALTER TABLE {$this->prefix}campaigns ALTER COLUMN allow_restart TYPE BOOLEAN USING allow_restart::boolean");
            $this->addSql("ALTER TABLE {$this->prefix}campaigns ALTER COLUMN allow_restart SET NOT NULL");
        } else {
            $this->addSql("ALTER TABLE {$this->prefix}campaigns MODIFY allow_restart BOOLEAN NOT NULL");
        }
    }
}
