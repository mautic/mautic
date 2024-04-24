<?php

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

class Version20231207235400 extends AbstractMauticMigration
{
    public function preUp(Schema $schema): void
    {
        $table = $schema->getTable($this->prefix.'forms');
        if ($table->hasColumn('lang')) {
            throw new SkipMigration('Schema includes this migration');
        }
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE '.$this->prefix.'forms ADD lang VARCHAR(191)  DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE '.$this->prefix.'forms  DROP lang');
    }
}
