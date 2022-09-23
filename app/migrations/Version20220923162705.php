<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20220923162705 extends AbstractMauticMigration
{

    /**
     * @throws SkipMigration
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema): void
    {
        if ($schema->getTable("{$this->prefix}page_hits")->hasColumn("{$this->prefix}generated_date_diff")) {
            throw new SkipMigration('Schema includes this migration');
        }
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            "ALTER TABLE {$this->prefix}page_hits ADD COLUMN {$this->prefix}generated_date_diff INT(11) GENERATED ALWAYS AS (timestampdiff(SECOND,`date_hit`,`date_left`)) STORED"
        );
    }
}
