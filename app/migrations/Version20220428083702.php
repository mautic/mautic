<?php

declare(strict_types=1);

/*
 * @copyright   2021 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        https://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20220428083702 extends AbstractMauticMigration
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
