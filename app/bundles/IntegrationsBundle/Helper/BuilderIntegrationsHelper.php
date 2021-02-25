<?php

declare(strict_types=1);

/*
 * @copyright   2021 Mautic. All rights reserved
 * @author      Mautic Contributors.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\IntegrationsBundle\Helper;

use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Integration\Interfaces\BuilderInterface;
use Mautic\PluginBundle\Entity\Integration;

class BuilderIntegrationsHelper
{
    /**
     * @var BuilderInterface[]
     */
    private $builders = [];

    /**
     * @var IntegrationsHelper
     */
    private $integrationsHelper;

    public function __construct(IntegrationsHelper $integrationsHelper)
    {
        $this->integrationsHelper = $integrationsHelper;
    }

    /**
     * Returns the first enabled builder that supports the given feature.
     *
     * @throws IntegrationNotFoundException
     */
    public function getBuilder(string $feature): BuilderInterface
    {
        foreach ($this->builders as $builder) {
            // Ensure the configuration is hydrated
            $this->integrationsHelper->getIntegrationConfiguration($builder);

            if ($builder->isSupported($feature) && $builder->getIntegrationConfiguration()->getIsPublished()) {
                return $builder;
            }
        }

        throw new IntegrationNotFoundException();
    }

    public function getBuilderNames(): array
    {
        $names = [];
        foreach ($this->builders as $builder) {
            $names[$builder->getName()] = $builder->getDisplayName();
        }

        return $names;
    }

    public function addIntegration(BuilderInterface $integration): void
    {
        $this->builders[$integration->getName()] = $integration;
    }

    /**
     * @throws IntegrationNotFoundException
     */
    public function getIntegration(string $integration): BuilderInterface
    {
        if (!isset($this->builders[$integration])) {
            throw new IntegrationNotFoundException("$integration either doesn't exist or has not been tagged with mautic.builder_integration");
        }

        // Ensure the configuration is hydrated
        $this->integrationsHelper->getIntegrationConfiguration($this->builders[$integration]);

        return $this->builders[$integration];
    }

    public function saveIntegrationConfiguration(Integration $integrationConfiguration): void
    {
        $this->integrationsHelper->saveIntegrationConfiguration($integrationConfiguration);
    }
}
