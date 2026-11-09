<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\CoreBundle\Helper\Serializer;

/**
 * OneSignal moved from the PluginBundle AbstractIntegration form, which stored its feature settings
 * flat, to the IntegrationsBundle config form, which stores them under the "integration" key.
 */
final class Version20261109120000 extends AbstractMauticMigration
{
    protected const string TABLE_NAME = 'plugin_integration_settings';

    private const array LEGACY_KEYS = ['subdomain_name', 'platforms'];

    public function getDescription(): string
    {
        return 'Move OneSignal feature settings under the "integration" key used by the IntegrationsBundle config form';
    }

    public function up(Schema $schema): void
    {
        $table = $this->getPrefixedTableName();
        $row   = $this->connection->fetchAssociative(
            sprintf('SELECT id, feature_settings FROM %s WHERE name = :name', $table),
            ['name' => 'OneSignal']
        );

        if (false === $row || empty($row['feature_settings'])) {
            return;
        }

        $settings = Serializer::decode($row['feature_settings']);
        if (!is_array($settings)) {
            return;
        }

        $integrationSettings = $settings['integration'] ?? [];
        $moved               = false;
        foreach (self::LEGACY_KEYS as $key) {
            if (!array_key_exists($key, $settings)) {
                continue;
            }

            // a value already saved by the new form wins
            $integrationSettings[$key] ??= $settings[$key];
            unset($settings[$key]);
            $moved = true;
        }

        if (!$moved) {
            return;
        }

        $settings['integration'] = $integrationSettings;
        $this->connection->update($table, ['feature_settings' => serialize($settings)], ['id' => $row['id']]);
    }
}
