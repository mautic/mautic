<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic Inc.
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Integration;

use Mautic\PluginBundle\Entity\Integration;
use MauticPlugin\IntegrationsBundle\Helper\BC\BCIntegrationFormsHelperTrait;
use MauticPlugin\IntegrationsBundle\Helper\BC\BCIntegrationHelperTrait;
use MauticPlugin\IntegrationsBundle\Integration\Interfaces\BasicInterface;
use MauticPlugin\IntegrationsBundle\Integration\Interfaces\IntegrationInterface;

/**
 * Class AbstractIntegration.
 */
abstract class BasicIntegration implements BasicInterface, IntegrationInterface
{
    use BCIntegrationHelperTrait;
    use BCIntegrationFormsHelperTrait;

    /**
     * @var Integration
     */
    private $integration;

    /**
     * @inheritdoc
     */
    public function isCoreIntegration(): bool
    {
        return false;
    }

    /**
     * @return Integration
     */
    public function getIntegrationConfiguration(): Integration
    {
        return $this->integration;
    }

    /**
     * @param Integration $integration
     */
    public function setIntegrationConfiguration(Integration $integration)
    {
        $this->integration = $integration;
    }

    /**
     * Check if Integration entity has been set to prevent PHP fatal error with using getIntegrationEntity
     *
     * @return bool
     */
    public function hasIntegrationConfiguration(): bool
    {
        return !empty($this->integration);
    }
}
