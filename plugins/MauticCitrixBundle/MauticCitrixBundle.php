<?php

namespace MauticPlugin\MauticCitrixBundle;

use Mautic\PluginBundle\Bundle\PluginBundleBase;
use MauticPlugin\MauticCitrixBundle\Helper\CitrixHelper;

/**
 * Class MauticCitrixBundle.
 */
class MauticCitrixBundle extends PluginBundleBase
{
    private \Mautic\PluginBundle\Helper\IntegrationHelper $integrationHelper;
    private \Psr\Log\LoggerInterface $logger;
    private \Symfony\Bundle\FrameworkBundle\Routing\Router $router;
    public function __construct(\Mautic\PluginBundle\Helper\IntegrationHelper $integrationHelper, \Psr\Log\LoggerInterface $logger, \Symfony\Bundle\FrameworkBundle\Routing\Router $router)
    {
        $this->integrationHelper = $integrationHelper;
        $this->logger = $logger;
        $this->router = $router;
    }
    public function boot()
    {
        parent::boot();

        CitrixHelper::init(
            $this->integrationHelper,
            $this->logger,
            $this->router
        );
    }
}
