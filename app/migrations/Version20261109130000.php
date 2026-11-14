<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;
use Mautic\CoreBundle\Helper\Serializer;

/**
 * Twilio moved from the PluginBundle AbstractIntegration form, which stored its feature settings
 * flat, to the IntegrationsBundle config form, which stores them under the "integration" key.
 */
final class Version20261109130000 extends PreUpAssertionMigration
{
    protected const string TABLE_NAME = 'plugin_integration_settings';

    private const string INTEGRATION_NAME = 'Twilio';

    private const array LEGACY_KEYS = ['messaging_service_sid', 'frequency_number', 'frequency_time'];

    public function getDescription(): string
    {
        return 'Move Twilio feature settings under the "integration" key used by the IntegrationsBundle config form';
    }

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema): bool => null === $this->findLegacySettings(),
            'Twilio has no feature settings left to move under the "integration" key'
        );
    }

    public function up(Schema $schema): void
    {
        $legacy = $this->findLegacySettings();
        if (null === $legacy) {
            return;
        }

        [$id, $settings] = $legacy;

        $integrationSettings = $settings['integration'] ?? [];
        foreach (self::LEGACY_KEYS as $key) {
            if (!array_key_exists($key, $settings)) {
                continue;
            }

            // a value already saved by the new form wins
            $integrationSettings[$key] ??= $settings[$key];
            unset($settings[$key]);
        }

        $settings['integration'] = $integrationSettings;
        $this->connection->update($this->getPrefixedTableName(), ['feature_settings' => serialize($settings)], ['id' => $id]);
    }

    /**
     * The Twilio row's id and feature settings, or null when there is no row or no flat legacy key to move.
     *
     * @return array{int|string, array<mixed>}|null
     */
    private function findLegacySettings(): ?array
    {
        $row = $this->connection->fetchAssociative(
            sprintf('SELECT id, feature_settings FROM %s WHERE name = :name', $this->getPrefixedTableName()),
            ['name' => self::INTEGRATION_NAME]
        );

        if (false === $row || empty($row['feature_settings'])) {
            return null;
        }

        $settings = Serializer::decode($row['feature_settings']);
        if (!is_array($settings) || [] === array_intersect(self::LEGACY_KEYS, array_keys($settings))) {
            return null;
        }

        return [$row['id'], $settings];
    }
}
