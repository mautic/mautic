<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20210115065034 extends AbstractMauticMigration
{
    public function preUp(Schema $schema): void
    {
        if ($schema->getTable($this->prefix.'emails')->hasColumn('preheader_text')) {
            throw new SkipMigration('The emails table already includes the preheader_text column');
        }
    }

    public function up(Schema $schema): void
    {
        $schema->getTable($this->getPrefixedTableName('emails'))->addColumn('preheader_text', 'string', ['length' => 130, 'notnull' => false]);
    }

    public function down(Schema $schema): void
    {
        $schema->getTable($this->getPrefixedTableName('emails'))->dropColumn('preheader_text');
    }
}
