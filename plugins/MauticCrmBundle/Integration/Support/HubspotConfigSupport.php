<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCrmBundle\Integration\Support;

use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormAuthInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormFeatureSettingsInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormFeaturesInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormSyncInterface;
use Mautic\IntegrationsBundle\Sync\SyncService\SyncServiceInterface;
use MauticPlugin\MauticCrmBundle\Form\Type\HubspotFeatureSettingsType;
use MauticPlugin\MauticCrmBundle\Form\Type\HubspotKeysType;
use MauticPlugin\MauticCrmBundle\Hubspot\Client\HubspotClient;
use MauticPlugin\MauticCrmBundle\Hubspot\Config;
use MauticPlugin\MauticCrmBundle\Hubspot\Sync\FieldRepository;
use MauticPlugin\MauticCrmBundle\Hubspot\Sync\MappingManualFactory;
use MauticPlugin\MauticCrmBundle\Integration\HubspotIntegration;
use Symfony\Component\DependencyInjection\Attribute\AutowireServiceClosure;
use Symfony\Contracts\Translation\TranslatorInterface;

final class HubspotConfigSupport extends HubspotIntegration implements ConfigFormInterface, ConfigFormAuthInterface, ConfigFormFeaturesInterface, ConfigFormFeatureSettingsInterface, ConfigFormSyncInterface
{
    /**
     * @param \Closure(): SyncServiceInterface $syncService
     */
    public function __construct(
        #[AutowireServiceClosure(SyncServiceInterface::class)]
        \Closure $syncService,
        Config $config,
        TranslatorInterface $translator,
        private readonly FieldRepository $fieldRepository,
    ) {
        parent::__construct($syncService, $config, $translator);
    }

    /**
     * Use the default.
     */
    public function getConfigFormName(): ?string
    {
        return null;
    }

    /**
     * Use the default.
     */
    public function getConfigFormContentTemplate(): ?string
    {
        return null;
    }

    public function getAuthConfigFormName(): string
    {
        return HubspotKeysType::class;
    }

    public function getFeatureSettingsConfigFormName(): string
    {
        return HubspotFeatureSettingsType::class;
    }

    /**
     * @return array<string, string>
     */
    public function getSupportedFeatures(): array
    {
        return [
            ConfigFormFeaturesInterface::FEATURE_SYNC      => 'mautic.integration.feature.sync',
            ConfigFormFeaturesInterface::FEATURE_PUSH_LEAD => 'mautic.integration.form.feature.push_lead',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getSyncConfigObjects(): array
    {
        return [
            HubspotClient::CONTACTS  => 'mautic.hubspot.object.contact',
            HubspotClient::COMPANIES => 'mautic.hubspot.object.company',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getSyncMappedObjects(): array
    {
        return MappingManualFactory::MAUTIC_OBJECTS;
    }

    public function getRequiredFieldsForMapping(string $object): array
    {
        return $this->fieldRepository->getRequiredFieldsForMapping($object);
    }

    public function getOptionalFieldsForMapping(string $object): array
    {
        return $this->fieldRepository->getOptionalFieldsForMapping($object);
    }

    public function getAllFieldsForMapping(string $object): array
    {
        return [...$this->getRequiredFieldsForMapping($object), ...$this->getOptionalFieldsForMapping($object)];
    }

    public function getSyncConfigFormName(): ?string
    {
        return null;
    }
}
