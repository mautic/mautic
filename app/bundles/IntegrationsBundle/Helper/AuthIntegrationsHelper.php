<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Helper;

use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Integration\Interfaces\AuthenticationInterface;
use Mautic\PluginBundle\Entity\Integration;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class AuthIntegrationsHelper
{
    /**
     * @var AuthenticationInterface[]
     */
    private array $integrations = [];

    /**
     * @param iterable<AuthenticationInterface> $integrations
     */
    public function __construct(
        private readonly IntegrationsHelper $integrationsHelper,
        #[AutowireIterator('mautic.authentication_integration')]
        iterable $integrations = [],
    ) {
        foreach ($integrations as $integration) {
            $this->addIntegration($integration);
        }
    }

    private function addIntegration(AuthenticationInterface $integration): void
    {
        $this->integrations[$integration->getName()] = $integration;
    }

    /**
     * @throws IntegrationNotFoundException
     */
    public function getIntegration(string $integration): AuthenticationInterface
    {
        if (!isset($this->integrations[$integration])) {
            throw new IntegrationNotFoundException("{$integration} either doesn't exist or has not been tagged with mautic.authentication_integration");
        }

        // Ensure the configuration is hydrated
        $this->integrationsHelper->getIntegrationConfiguration($this->integrations[$integration]);

        return $this->integrations[$integration];
    }

    public function saveIntegrationConfiguration(Integration $integrationConfiguration): void
    {
        $this->integrationsHelper->saveIntegrationConfiguration($integrationConfiguration);
    }
}
