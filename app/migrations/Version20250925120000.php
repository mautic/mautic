<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20250925120000 extends AbstractMauticMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE `' . $this->prefix . 'plugin_uli_unique_logins` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `hash` varchar(64) NOT NULL,
            `user_id` int(11) NOT NULL,
            `ttl` datetime NOT NULL,
            `date_created` datetime NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `UNIQ_ULI_HASH` (`hash`),
            KEY `IDX_ULI_TTL` (`ttl`),
            KEY `IDX_ULI_USER` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS `' . $this->prefix . 'plugin_uli_unique_logins`');
    }
}