<?php

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20200220172041 extends AbstractMauticMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE `{$this->prefix}categories` SET bundle = 'messages' WHERE bundle = '0';");
    }
}
