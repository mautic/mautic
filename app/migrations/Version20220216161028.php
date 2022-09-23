<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20220216161028 extends AbstractMauticMigration
{
    public function up(Schema $schema): void
    {
        $oldAndNewValues = [
            $this->connection->quote('Val d\'Oise') => $this->connection->quote('Val-d\'Oise'),
            $this->connection->quote('Réunion')     => $this->connection->quote('La Réunion'),
        ];

        foreach ($oldAndNewValues as $old => $new) {
            $this->addSql("UPDATE `{$this->prefix}leads` SET `state` = {$new} WHERE `state` = {$old}");
            $this->addSql("UPDATE `{$this->prefix}companies` SET `companystate` = {$new} WHERE `companystate` = {$old}");
        }

        $filterOldAndNewValues = [
            $this->connection->quote('s:6:"filter";s:10:"Val d\'Oise";') => $this->connection->quote('s:6:"filter";s:10:"Val-d\'Oise";'),
            $this->connection->quote('s:6:"filter";s:8:"Réunion";')      => $this->connection->quote('s:6:"filter";s:11:"La Réunion";'),
        ];
        foreach ($filterOldAndNewValues as $old => $new) {
            $this->addSql("UPDATE `{$this->prefix}dynamic_content` SET `filters` = REPLACE(`filters`, {$old}, {$new})");
            $this->addSql("UPDATE `{$this->prefix}lead_lists` SET `filters` = REPLACE(`filters`, {$old}, {$new})");
            $this->addSql("UPDATE `{$this->prefix}emails` SET `dynamic_content` = REPLACE(`dynamic_content`, {$old}, {$new})");
        }
    }
}
