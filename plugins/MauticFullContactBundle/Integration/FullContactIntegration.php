<?php

namespace MauticPlugin\MauticFullContactBundle\Integration;

use Mautic\IntegrationsBundle\Integration\BasicIntegration;
use Mautic\IntegrationsBundle\Integration\ConfigurationTrait;
use Mautic\IntegrationsBundle\Integration\Interfaces\BasicInterface;

class FullContactIntegration extends BasicIntegration implements BasicInterface
{
    use ConfigurationTrait;

    public const NAME         = 'fullcontact';
    public const DISPLAY_NAME = 'Full Contact';

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getDisplayName(): string
    {
        return self::DISPLAY_NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getIcon(): string
    {
        return 'plugins/MauticFullContactBundle/Assets/img/fullcontact.png';
    }

    public function getAuthenticationType(): string
    {
        return 'none';
    }

    public function shouldAutoUpdate()
    {
        $integrationConfiguration = $this->getIntegrationConfiguration();
        $featureSettings          = $integrationConfiguration->getApiKeys();

        return (isset($featureSettings['auto_update'])) ? (bool) $featureSettings['auto_update'] : false;
    }
}
