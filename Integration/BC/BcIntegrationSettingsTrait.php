<?php

/*
 * @copyright   2018 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */


namespace MauticPlugin\IntegrationsBundle\Integration\BC;


use Mautic\PluginBundle\Entity\Integration;

/**
 * Trait BcIntegrationSettingsTrait
 */
trait BcIntegrationSettingsTrait
{
    /**
     * @deprecated Use setIntegrationConfiguration
     *
     * @param Integration $integration
     */
    public function setIntegrationSettings(Integration $integration)
    {
        $this->setIntegrationConfiguration($integration);
    }

    /**
     * @deprecated Use getIntegrationConfiguration
     *
     * @return Integration|null
     */
    public function getIntegrationSettings(): ?Integration
    {
        return ($this->hasIntegrationConfiguration()) ? $this->getIntegrationConfiguration() : null;
    }

    /**
     * @deprecated Implement ConfigFormFeaturesInterface instead
     *
     * @return array
     */
    public function getSupportedFeatures(): array
    {
        return [];
    }
}