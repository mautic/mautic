<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20221014081742 extends AbstractMauticMigration
{
    public function preUp(Schema $schema): void
    {
        $table = $schema->getTable($this->getTableName());

        if ($table->hasColumn('payload_compressed')) {
            throw new SkipMigration('Schema includes this migration');
        }
    }

    public function up(Schema $schema): void
    {
        $this->addSql(sprintf('ALTER TABLE %s ADD payload_compressed MEDIUMBLOB DEFAULT NULL, CHANGE payload payload LONGTEXT DEFAULT NULL', $this->getTableName()));
    }

    private function getTableName(): string
    {
        return $this->prefix.'webhook_queue';
    }
}
