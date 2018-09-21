<?php


namespace MauticPlugin\IntegrationsBundle\Helper;


use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Entity\IntegrationRepository;
use MauticPlugin\IntegrationsBundle\Exception\IntegrationNotFoundException;
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
     * AuthIntegrationsHelper constructor.
     *
     * @param IntegrationRepository $integrationRepository
     */
    public function __construct(IntegrationRepository $integrationRepository)
    {
        $this->integrationRepository = $integrationRepository;
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
     * @param IntegrationInterface $integration
     *
     * @return Integration
     * @throws IntegrationNotFoundException
     */
    public function getIntegrationConfiguration(IntegrationInterface $integration)
    {
        if (!$integration->hasIntegrationConfiguration()) {
            /** @var Integration $configuration */
            $configuration = $this->integrationRepository->findOneBy(['integration' => $integration]);

            if (!$configuration) {
                throw new IntegrationNotFoundException("{$integration->getName()} doesn't exist in the database");
            }

            $integration->setIntegrationConfiguration($configuration);
        }

        return $integration->getIntegrationConfiguration();
    }
}