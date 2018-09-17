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

use Doctrine\ORM\EntityManager;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Integration\UnifiedIntegrationInterface;
use MauticPlugin\IntegrationsBundle\Helpers\BCIntegrationFormsHelperTrait;
use MauticPlugin\IntegrationsBundle\Helpers\BCIntegrationHelperTrait;
use MauticPlugin\IntegrationsBundle\Integration\Interfaces\BasicInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Class AbstractIntegration.
 */
abstract class BasicIntegration implements BasicInterface, UnifiedIntegrationInterface
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
    public function getIntegration(): Integration
    {
        return $this->integration;
    }

    /**
     * @param Integration $integration
     */
    public function setIntegration(Integration $integration)
    {
        $this->integration = $integration;
    }

    /**
     * Check if Integration entity has been set to prevent PHP fatal error with using getIntegrationEntity
     *
     * @return bool
     */
    public function hasIntegration(): bool
    {
        return !empty($this->integration);
    }
}
