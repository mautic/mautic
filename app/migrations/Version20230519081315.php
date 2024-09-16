<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use MauticPlugin\MauticCrmBundle\Integration\PipedriveIntegration;

final class Version20230519081315 extends AbstractMauticMigration
{
    public function preUp(Schema $schema): void
    {
        $connection  = $this->entityManager->getConnection();
        $pluginInUse = $connection->prepare("SELECT 1 from {$this->prefix}plugin_integration_settings WHERE name='Pipedrive' AND is_published = 1")->executeQuery()->rowCount();

        if (!$pluginInUse) {
            throw new SkipMigration('The Pipedrive plugin is not used on this instance, no need to add deprecation notifications');
        }
    }

    public function up(Schema $schema): void
    {
        $table   = "{$this->prefix}notifications";
        $header  = 'The Pipedrive plugin will be removed in Mautic 5.0.0';
        $message = PipedriveIntegration::DEPRECATION_MESSAGE;
        $sql     = "INSERT INTO {$table} (user_id, type, header, message, date_added, icon_class, is_read) SELECT u.id ,'warning', '{$header}', '{$message}', NOW(), 'fa-warning', 0 FROM {$this->prefix}users u";
        $this->addSql($sql);
    }
}
