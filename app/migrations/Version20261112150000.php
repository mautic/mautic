<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;
use Mautic\CoreBundle\Helper\Serializer;

/**
 * Hubspot moved from the PluginBundle AbstractIntegration, with its own field matching and contact links, to the
 * IntegrationsBundle sync engine. This moves the saved field mapping into the sync settings, turns the "get_leads"
 * feature into "sync", and copies the contact links so that linked contacts are not created again.
 */
final class Version20261112150000 extends PreUpAssertionMigration
{
    protected const string TABLE_NAME = 'plugin_integration_settings';

    private const string INTEGRATION_NAME = 'Hubspot';

    private const array LEGACY_KEYS = ['objects', 'leadFields', 'companyFields', 'update_mautic', 'update_mautic_company'];

    /**
     * The former mapping's special Mautic values and the sync engine's equivalents.
     */
    private const array SPECIAL_FIELDS = [
        'mauticContactId'                   => 'mautic_internal_id',
        'mauticContactTimelineLink'         => 'mautic_internal_contact_timeline',
        'mauticContactIsContactableByEmail' => 'mautic_internal_dnc_email',
    ];

    public function getDescription(): string
    {
        return 'Move the Hubspot field mapping and contact links to the IntegrationsBundle sync engine';
    }

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema): bool => null === $this->findSettings() && 0 === $this->countLinksToCopy(),
            'Hubspot has no legacy settings or contact links to migrate'
        );
    }

    public function up(Schema $schema): void
    {
        $settings = $this->findSettings();
        if (null !== $settings) {
            $this->migrateSettings(...$settings);
        }

        $this->copyLinks();
    }

    /**
     * @param array<mixed>      $featureSettings
     * @param array<mixed>|null $supportedFeatures
     */
    private function migrateSettings(int|string $id, array $featureSettings, ?array $supportedFeatures): void
    {
        if (!isset($featureSettings['sync']) && array_intersect(self::LEGACY_KEYS, array_keys($featureSettings))) {
            $objects = [];
            foreach ((array) ($featureSettings['objects'] ?? []) as $object) {
                $objects[] = 'company' === $object ? 'companies' : 'contacts';
            }

            $featureSettings['sync'] = [
                'objects'       => array_values(array_unique($objects ?: ['contacts'])),
                'fieldMappings' => [
                    'contacts'  => $this->toFieldMappings((array) ($featureSettings['leadFields'] ?? []), (array) ($featureSettings['update_mautic'] ?? [])),
                    'companies' => $this->toFieldMappings((array) ($featureSettings['companyFields'] ?? []), (array) ($featureSettings['update_mautic_company'] ?? [])),
                ],
            ];
        }

        foreach (self::LEGACY_KEYS as $key) {
            unset($featureSettings[$key]);
        }

        $update = ['feature_settings' => serialize($featureSettings)];
        if (is_array($supportedFeatures) && in_array('get_leads', $supportedFeatures, true)) {
            $supportedFeatures = array_values(array_unique(array_map(static fn (string $feature): string => 'get_leads' === $feature ? 'sync' : $feature, array_filter($supportedFeatures, 'is_string'))));
            $update['supported_features'] = serialize($supportedFeatures);
        }

        $this->connection->update($this->getPrefixedTableName(), $update, ['id' => $id]);
    }

    /**
     * @param array<mixed> $fields    Mautic field by Hubspot property, or the former form's i_/m_ pairs
     * @param array<mixed> $directions 1 when Hubspot updates Mautic, 0 when Mautic updates Hubspot, by Hubspot property
     *
     * @return array<string, array{mappedField: string, syncDirection: string}>
     */
    private function toFieldMappings(array $fields, array $directions): array
    {
        if (isset($fields['m_1'])) {
            $pairs = [];
            for ($i = 1; isset($fields['i_'.$i]); ++$i) {
                if (isset($fields['m_'.$i])) {
                    $pairs[$fields['i_'.$i]] = $fields['m_'.$i];
                }
            }
            $fields = $pairs;
        }

        $mappings = [];
        foreach ($fields as $property => $mauticField) {
            if (!is_string($mauticField) || '' === $mauticField) {
                continue;
            }

            $mappings[(string) $property] = [
                'mappedField'   => self::SPECIAL_FIELDS[$mauticField] ?? $mauticField,
                // Both directions synced before; the former setting only chose which side wins
                'syncDirection' => isset($directions[$property]) && !$directions[$property] ? 'integration' : 'bidirectional',
            ];
        }

        return $mappings;
    }

    private function copyLinks(): void
    {
        $this->connection->executeStatement(sprintf(
            'INSERT INTO %1$s (date_created, integration, internal_object_name, internal_object_id, integration_object_name, integration_object_id, last_sync_date, internal_storage, is_deleted)
            SELECT ie.date_added, ie.integration, ie.internal_entity, ie.internal_entity_id, %3$s, ie.integration_entity_id, COALESCE(ie.last_sync_date, ie.date_added), \'[]\', 0
            FROM %2$s ie
            WHERE %4$s
            AND NOT EXISTS (
                SELECT 1 FROM %1$s som
                WHERE som.integration = ie.integration AND som.internal_object_name = ie.internal_entity AND som.internal_object_id = ie.internal_entity_id
            )',
            $this->getPrefixedTableName('sync_object_mapping'),
            $this->getPrefixedTableName('integration_entity'),
            "CASE WHEN ie.internal_entity = 'company' THEN 'companies' ELSE 'contacts' END",
            $this->getLinksCondition()
        ), ['integration' => self::INTEGRATION_NAME]);
    }

    private function countLinksToCopy(): int
    {
        return (int) $this->connection->fetchOne(sprintf(
            'SELECT COUNT(*) FROM %s ie WHERE %s AND NOT EXISTS (
                SELECT 1 FROM %s som
                WHERE som.integration = ie.integration AND som.internal_object_name = ie.internal_entity AND som.internal_object_id = ie.internal_entity_id
            )',
            $this->getPrefixedTableName('integration_entity'),
            $this->getLinksCondition(),
            $this->getPrefixedTableName('sync_object_mapping')
        ), ['integration' => self::INTEGRATION_NAME]);
    }

    /**
     * Hubspot's links, the newest one when a record was pushed more than once.
     */
    private function getLinksCondition(): string
    {
        return sprintf(
            "ie.integration = :integration AND ie.internal_entity IN ('lead', 'company') AND ie.internal_entity_id IS NOT NULL AND ie.integration_entity_id IS NOT NULL AND ie.integration_entity_id <> ''
            AND ie.id = (
                SELECT MAX(newest.id) FROM %s newest
                WHERE newest.integration = ie.integration AND newest.internal_entity = ie.internal_entity AND newest.internal_entity_id = ie.internal_entity_id
            )",
            $this->getPrefixedTableName('integration_entity')
        );
    }

    /**
     * The Hubspot row's id, feature settings and supported features, or null when there is nothing to migrate.
     *
     * @return array{int|string, array<mixed>, array<mixed>|null}|null
     */
    private function findSettings(): ?array
    {
        $row = $this->connection->fetchAssociative(
            sprintf('SELECT id, feature_settings, supported_features FROM %s WHERE name = :name', $this->getPrefixedTableName()),
            ['name' => self::INTEGRATION_NAME]
        );

        if (false === $row) {
            return null;
        }

        $featureSettings   = empty($row['feature_settings']) ? [] : Serializer::decode($row['feature_settings']);
        $supportedFeatures = empty($row['supported_features']) ? null : Serializer::decode($row['supported_features']);
        $featureSettings   = is_array($featureSettings) ? $featureSettings : [];
        $supportedFeatures = is_array($supportedFeatures) ? $supportedFeatures : null;

        $hasLegacySettings = [] !== array_intersect(self::LEGACY_KEYS, array_keys($featureSettings));
        $hasLegacyFeature  = null !== $supportedFeatures && in_array('get_leads', $supportedFeatures, true);

        return $hasLegacySettings || $hasLegacyFeature ? [$row['id'], $featureSettings, $supportedFeatures] : null;
    }
}
