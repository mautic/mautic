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
final class Version20200513162918 extends AbstractMauticMigration
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
        $schema->getTable($this->getPrefixedTableName('email_copies'))->addColumn('body_text', 'text', ['notnull' => false]);
    }

    public function down(Schema $schema): void
    {
        $schema->getTable($this->getPrefixedTableName('email_copies'))->dropColumn('body_text');
    }
}
