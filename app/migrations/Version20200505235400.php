<?php

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

class Version20200505235400 extends AbstractMauticMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->getTable($this->prefix.'forms');
        if ($table->hasColumn('lang')) {
            throw new SkipMigration('Schema includes this migration');
        }

        $this->addSql('ALTER TABLE '.$this->prefix.'forms ADD lang VARCHAR(255)  DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE '.$this->prefix.'forms  DROP lang');
    }
}
