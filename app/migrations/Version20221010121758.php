<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20221010121758 extends AbstractMauticMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE `{$this->prefix}leads` SET `state` = 'Uttarakhand' WHERE `state` = 'Uttaranchal'");
    }
}
