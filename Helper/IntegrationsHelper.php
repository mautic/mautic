<?php


namespace MauticPlugin\IntegrationsBundle\Helper;


use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Entity\IntegrationRepository;
use MauticPlugin\IntegrationsBundle\Exception\IntegrationNotFoundException;
use MauticPlugin\IntegrationsBundle\Facade\EncryptionService;
use MauticPlugin\IntegrationsBundle\Integration\Interfaces\IntegrationInterface;

class IntegrationsHelper
{
    /**
     * @var IntegrationInterface[]
     */
    private $integrations = [];

    /**
     * @var IntegrationRepository
     */
    private $integrationRepository;

    /**
     * @var EncryptionService
     */
    private $encryptionService;

    /**
     * @var array
     */
    private $decryptedIntegrationConfigurations = [];

    /**
     * IntegrationsHelper constructor.
     *
     * @param IntegrationRepository $integrationRepository
     * @param EncryptionService     $encryptionService
     */
    public function __construct(IntegrationRepository $integrationRepository, EncryptionService $encryptionService)
    {
        $this->integrationRepository = $integrationRepository;
        $this->encryptionService     = $encryptionService;
    }

    /**
     * @param IntegrationInterface $integration
     */
    public function addIntegration(IntegrationInterface $integration)
    {
        $this->integrations[$integration->getName()] = $integration;
    }

    /**
     * @param string $integration
     *
     * @return IntegrationInterface
     * @throws IntegrationNotFoundException
     */
    public function getIntegration(string $integration)
    {
        if (!isset($this->integrations[$integration])){
            throw new IntegrationNotFoundException("$integration either doesn't exist or has not been tagged with mautic.basic_integration");
        }

        // Ensure the configuration is hydrated
        $this->getIntegrationConfiguration($this->integrations[$integration]);

        return $this->integrations[$integration];
    }

    /**
     * @param Integration $configuration
     */
    public function saveIntegrationConfiguration(Integration $configuration)
    {
        // Encrypt the keys before saving
        $decryptedApiKeys = $configuration->getApiKeys();
        $encryptedApiKeys = $this->encryptionService->encrypt($decryptedApiKeys);
        $configuration->setApiKeys($encryptedApiKeys);

        // Save
        $this->integrationRepository->saveEntity($configuration);

        // Restore decrypted for use
        $configuration->setApiKeys($decryptedApiKeys);
    }

    /**
     * @param IntegrationInterface $integration
     *
     * @return Integration
     * @throws IntegrationNotFoundException
     */
    public function getIntegrationConfiguration(IntegrationInterface $integration)
    {
        if (!$integration->hasIntegrationConfiguration()) {
            /** @var Integration $configuration */
            $configuration = $this->integrationRepository->findOneBy(['name' => $integration->getName()]);

            if (!$configuration) {
                throw new IntegrationNotFoundException("{$integration->getName()} doesn't exist in the database");
            }

            if (!isset($this->decryptedIntegrationConfigurations[$integration->getName()])) {
                $encryptedApiKeys = $configuration->getApiKeys();
                $decryptedApiKeys = $this->encryptionService->decrypt($encryptedApiKeys);
                $configuration->setApiKeys($decryptedApiKeys);

                $this->decryptedIntegrationConfigurations[$integration->getName()] = true;
            }

            $integration->setIntegrationConfiguration($configuration);
        }

        return $integration->getIntegrationConfiguration();
    }
}