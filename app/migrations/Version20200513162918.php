<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Migration for removing online status.
 */
class Version20200513162918 extends AbstractMauticMigration
{
    /**
     * @throws SkipMigration
     * @throws SchemaException
     */
    public function preUp(Schema $schema): void
    {
        if ($schema->getTable("{$this->prefix}email_copies")->hasColumn('body_text')) {
            throw new SkipMigration("The body_text column has already been added to the {$this->prefix}email_copies table.");
        }
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE {$this->prefix}email_copies ADD COLUMN `body_text` LONGTEXT NULL DEFAULT NULL AFTER `body`");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE {$this->prefix}email_copies DROP COLUMN `body_text`");
    }
}
