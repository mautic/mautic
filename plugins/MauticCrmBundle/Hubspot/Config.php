<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCrmBundle\Hubspot;

use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Helper\IntegrationsHelper;
use Mautic\IntegrationsBundle\Sync\DAO\Mapping\ObjectMappingDAO;
use Mautic\PluginBundle\Entity\Integration;
use MauticPlugin\MauticCrmBundle\Integration\HubspotIntegration;
use Symfony\Component\DependencyInjection\Attribute\AutowireServiceClosure;

/**
 * Reads the saved HubSpot settings.
 */
class Config
{
    public const string ACCESS_TOKEN = 'accessKey';

    /**
     * Feature setting: create HubSpot records for Mautic contacts and companies that are not linked yet.
     */
    public const string CREATE_RECORDS = 'createRecords';

    /**
     * @param \Closure(): IntegrationsHelper $integrationsHelper a closure, as IntegrationsHelper builds the Hubspot integrations, which need this
     */
    public function __construct(
        #[AutowireServiceClosure(IntegrationsHelper::class)]
        private readonly \Closure $integrationsHelper,
    ) {
    }

    public function getAccessToken(): ?string
    {
        $token = $this->getIntegrationEntity()?->getApiKeys()[self::ACCESS_TOKEN] ?? null;

        return is_string($token) && '' !== $token ? $token : null;
    }

    public function isPublished(): bool
    {
        return (bool) $this->getIntegrationEntity()?->getIsPublished();
    }

    public function createsRecords(): bool
    {
        return !empty($this->getFeatureSettings()['integration'][self::CREATE_RECORDS]);
    }

    /**
     * @return string[] the HubSpot objects enabled for the sync
     */
    public function getEnabledObjects(): array
    {
        $objects = $this->getFeatureSettings()['sync']['objects'] ?? [];

        return is_array($objects) ? array_values(array_filter($objects, is_string(...))) : [];
    }

    /**
     * @return array<string, array{mappedField: string, syncDirection: string}> by HubSpot property
     */
    public function getFieldMappings(string $object): array
    {
        $mappings = [];
        foreach ($this->getFeatureSettings()['sync']['fieldMappings'][$object] ?? [] as $property => $mapping) {
            if (!is_array($mapping) || empty($mapping['mappedField'])) {
                continue;
            }

            $mappings[(string) $property] = [
                'mappedField'   => (string) $mapping['mappedField'],
                'syncDirection' => (string) ($mapping['syncDirection'] ?? ObjectMappingDAO::SYNC_BIDIRECTIONALLY),
            ];
        }

        return $mappings;
    }

    /**
     * @return array<string, mixed>
     */
    private function getFeatureSettings(): array
    {
        return $this->getIntegrationEntity()?->getFeatureSettings() ?? [];
    }

    private function getIntegrationEntity(): ?Integration
    {
        try {
            return ($this->integrationsHelper)()->getIntegration(HubspotIntegration::NAME)->getIntegrationConfiguration();
        } catch (IntegrationNotFoundException) {
            return null;
        }
    }
}
