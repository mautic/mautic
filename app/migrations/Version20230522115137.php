<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use MauticPlugin\MauticCitrixBundle\Integration\CitrixAbstractIntegration;

final class Version20230522115137 extends AbstractMauticMigration
{
    /**
     * @throws SkipMigration
     */
    public function preUp(Schema $schema): void
    {
        $pluginInUse = false;
        try {
            $schema->getTable($this->prefix.'plugin_citrix_events');
            $pluginInUse = true;
        } catch (SchemaException $e) {
            // getTable will throw an exception if the table does not exist
            $pluginInUse = false;
        }

        if (!$pluginInUse) {
            throw new SkipMigration('The Citrix plugin is not used on this instance, no need to add deprecation notifications');
        }
    }

    public function up(Schema $schema): void
    {
        $table   = "{$this->prefix}notifications";
        $header  = 'The Citrix plugin will be removed in Mautic 5.0.0';
        $message = CitrixAbstractIntegration::DEPRECATION_MESSAGE;
        $sql     = "INSERT INTO {$table} (user_id, type, header, message, date_added, icon_class, is_read) SELECT u.id ,'warning', '{$header}', '{$message}', NOW(), 'fa-warning', 0 FROM {$this->prefix}users u";
        $this->addSql($sql);
    }
}
