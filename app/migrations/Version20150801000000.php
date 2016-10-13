<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Migrations\SkipMigrationException;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Addon to Plugin conversion.
 */
class Version20150801000000 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        if ($schema->hasTable($this->prefix.'plugins')) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql('ALTER TABLE '.$this->prefix.'addon_integration_settings DROP FOREIGN KEY '.$this->findPropertyName('addon_integration_settings', 'fk', 'CC642678'));

        $this->addSql('RENAME TABLE '.$this->prefix.'addons TO '.$this->prefix.'plugins');

        $this->addSql('ALTER TABLE '.$this->prefix.'plugins DROP COLUMN is_enabled');

        $this->addSql('RENAME TABLE '.$this->prefix.'addon_integration_settings TO '.$this->prefix.'plugin_integration_settings');

        $this->addSql('ALTER TABLE '.$this->prefix.'plugin_integration_settings CHANGE addon_id plugin_id int(11) DEFAULT NULL');

        $this->addSql('ALTER TABLE '.$this->prefix.'plugin_integration_settings ADD CONSTRAINT '.$this->generatePropertyName('plugin_integration_settings', 'fk', ['plugin_id']).'  FOREIGN KEY (plugin_id) REFERENCES '.$this->prefix.'plugins (id) ON DELETE CASCADE');
    }

    public function postUp(Schema $schema)
    {
        // Update event names
        $q = $this->connection->createQueryBuilder();
        $q->update(MAUTIC_TABLE_PREFIX.'campaign_events')
            ->set('type', $q->expr()->literal('plugin.leadpush'))
            ->where(
                $q->expr()->eq('type', $q->expr()->literal('addon.leadpush'))
            )
            ->execute();

        $q = $this->connection->createQueryBuilder();
        $q->update(MAUTIC_TABLE_PREFIX.'form_actions')
            ->set('type', $q->expr()->literal('plugin.leadpush'))
            ->where(
                $q->expr()->eq('type', $q->expr()->literal('addon.leadpush'))
            )
            ->execute();

        $q = $this->connection->createQueryBuilder();
        $q->update(MAUTIC_TABLE_PREFIX.'point_trigger_events')
            ->set('type', $q->expr()->literal('plugin.leadpush'))
            ->where(
                $q->expr()->eq('type', $q->expr()->literal('addon.leadpush'))
            )
            ->execute();

        // Update permissions
        $q = $this->connection->createQueryBuilder();
        $q->update(MAUTIC_TABLE_PREFIX.'permissions')
            ->set('bundle', $q->expr()->literal('plugin'))
            ->set('name', $q->expr()->literal('plugins'))
            ->where(
                $q->expr()->andX(
                    $q->expr()->eq('bundle', $q->expr()->literal('addon')),
                    $q->expr()->eq('name', $q->expr()->literal('addons'))
                )
            )
            ->execute();
    }
}
