<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20201228041109 extends AbstractMauticMigration
{
    /**
     * @throws SkipMigrationException
     */
    public function preUp(Schema $schema): void
    {
        //Not doing anything to throw SkipMigration here.
        //It is ok if the migration is already run and if the data types are LONGTEXT already
        //And if the alter statement changes it to LONGTEXT again
    }

    public function up(Schema $schema): void
    {
        // Changes remote_path and original_file_name columns to Longtext
        $this->addSql("ALTER TABLE {$this->prefix}assets MODIFY remote_path LONGTEXT ");
        $this->addSql("ALTER TABLE {$this->prefix}assets MODIFY original_file_name LONGTEXT ");
    }

    public function down(Schema $schema): void
    {
        // Changes remote_path and original_file_name columns to earlier VARCHAR(191)
        // Rolling back causes any long URLs or Filenames with more than 191 chars to be trimmed.
        $this->addSql("UPDATE {$this->prefix}assets SET remote_path = left(remote_path,191)");
        $this->addSql("UPDATE {$this->prefix}assets SET original_file_name = left(remote_path,191)");
        $this->addSql("ALTER TABLE {$this->prefix}assets MODIFY remote_path VARCHAR(191) DEFAULT '' ");
        $this->addSql("ALTER TABLE {$this->prefix}assets MODIFY original_file_name VARCHAR(191) DEFAULT '' ");
    }
}
