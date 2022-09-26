<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20220923162704 extends AbstractMauticMigration
{

    /**
     * @throws SkipMigration
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema): void
    {
        if (!$schema->getTable("{$this->prefix}page_hits")->hasIndex("{$this->prefix}code")) {
            throw new SkipMigration('Schema includes this migration');
        }
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX '.$this->prefix.'code ON '.$this->prefix.'page_hits');
    }
}
