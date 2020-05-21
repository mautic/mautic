<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFullContactBundle\Integration\Support;

use Mautic\IntegrationsBundle\Integration\DefaultConfigFormTrait;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormAuthInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormFeatureSettingsInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormFeaturesInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormSyncInterface;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Company;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;
use MauticPlugin\MauticFullContactBundle\Form\Type\ConfigAuthType;
use MauticPlugin\MauticFullContactBundle\Form\Type\ConfigFeaturesType;
use MauticPlugin\MauticFullContactBundle\Integration\FullContactIntegration;
use MauticPlugin\MauticFullContactBundle\Sync\Mapping\Field\FieldRepository;

class ConfigSupport extends FullContactIntegration implements ConfigFormInterface, ConfigFormAuthInterface, ConfigFormFeatureSettingsInterface, ConfigFormSyncInterface, ConfigFormFeaturesInterface
{
    use DefaultConfigFormTrait;

    /**
     * @var FieldRepository
     */
    private $fieldRepository;

    public function __construct(FieldRepository $fieldRepository)
    {
        $this->fieldRepository = $fieldRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthConfigFormName(): string
    {
        return ConfigAuthType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getFeatureSettingsConfigFormName(): string
    {
        return ConfigFeaturesType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedFeatures(): array
    {
        return [
          ConfigFormFeaturesInterface::FEATURE_SYNC => 'mautic.integration.feature.sync',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getSyncConfigObjects(): array
    {
        return [
          'Contact' => 'mautic.plugin.fullcontact.contact',
          'Company' => 'mautic.plugin.fullcontact.company',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getSyncMappedObjects(): array
    {
        return [
          'Contact' => Contact::NAME,
          'Company' => Company::NAME,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getRequiredFieldsForMapping(string $object): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getOptionalFieldsForMapping(string $object): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAllFieldsForMapping(string $object): array
    {
        return $this->fieldRepository->getAllFieldsForMapping($object);
    }
}
