<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20210203143600 extends AbstractMauticMigration
{
    /**
     * @throws SkipMigration
     */
    public function preUp(Schema $schema): void
    {
        $pagesTable = $schema->getTable(MAUTIC_TABLE_PREFIX.'pages');
        if ($pagesTable->hasColumn('head_script') && $pagesTable->hasColumn('footer_script')) {
            throw new SkipMigration('Schema includes this migration');
        }
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE {$this->prefix}pages ADD head_script LONGTEXT DEFAULT NULL");
        $this->addSql("ALTER TABLE {$this->prefix}pages ADD footer_script LONGTEXT DEFAULT NULL");
    }
}
