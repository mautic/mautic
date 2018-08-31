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

    /** @var Integration */
    private $integrationEntity;

    /** @var RouterInterface */
    private $router;

    /** @var EntityManager */
    private $entityManager;

    /** @inheritdoc */
    public function isCoreIntegration(): bool
    {
        return false;
    }

    /**
     * @return Integration
     */
    public function getIntegrationEntity(): Integration
    {
        return $this->integrationEntity;
    }

    /**
     * @param Integration $integrationEntity
     *
     * @return BasicIntegration
     */
    public function setIntegrationEntity(Integration $integrationEntity): BasicIntegration
    {
        $this->integrationEntity = $integrationEntity;

        return $this;
    }

    /**
     * @return RouterInterface
     */
    public function getRouter(): RouterInterface
    {
        return $this->router;
    }

    /**
     * @param RouterInterface $router
     *
     * @return BasicIntegration
     */
    public function setRouter(RouterInterface $router): BasicIntegration
    {
        $this->router = $router;

        return $this;
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager(): EntityManager
    {
        return $this->entityManager;
    }

    /**
     * @param EntityManager $entityManager
     *
     * @return BasicIntegration
     */
    public function setEntityManager(EntityManager $entityManager): BasicIntegration
    {
        $this->entityManager = $entityManager;

        return $this;
    }
}
