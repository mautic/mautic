<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20230307083702 extends AbstractMauticMigration
{
    public function up(Schema $schema): void
    {
        $old = $this->connection->quote('Swaziland');
        $new = $this->connection->quote('Eswatini');

        $this->addSql("UPDATE `{$this->prefix}leads` SET `country` = {$new} WHERE `country` = {$old}");
        $this->addSql("UPDATE `{$this->prefix}companies` SET `companycountry` = {$new} WHERE `companycountry` = {$old}");

        $old = $this->connection->quote('s:6:"filter";s:9:"Swaziland";');
        $new = $this->connection->quote('s:6:"filter";s:8:"Eswatini";');

        $this->addSql("UPDATE `{$this->prefix}dynamic_content` SET `filters` = REPLACE(`filters`, {$old}, {$new})");
        $this->addSql("UPDATE `{$this->prefix}lead_lists` SET `filters` = REPLACE(`filters`, {$old}, {$new})");
        $this->addSql("UPDATE `{$this->prefix}emails` SET `dynamic_content` = REPLACE(`dynamic_content`, {$old}, {$new})");
    }
}
