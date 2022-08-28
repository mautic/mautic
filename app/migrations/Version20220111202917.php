<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20220111202917 extends AbstractMauticMigration
{
    /**
     * @var string
     */
    private $table = 'lead_tags';

    /**
     * @throws SkipMigration
     */
    public function preUp(Schema $schema): void
    {
        $table              = $this->prefix.$this->table;
        $shouldRunMigration = !$schema->getTable($table)->hasColumn('description');

        if (!$shouldRunMigration) {
            throw new SkipMigration('Schema includes this migration');
        }
    }

    public function up(Schema $schema): void
    {
        $table = $this->prefix.$this->table;
        $sql   = "ALTER TABLE {$table} ADD description LONGTEXT DEFAULT NULL; ";
        $this->addSql($sql);
    }

    public function down(Schema $schema): void
    {
        $table = $this->prefix.$this->table;
        $sql   = "ALTER TABLE {$table} DROP description; ";
        $this->addSql($sql);
    }
}
