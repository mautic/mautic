<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\Event;

use Mautic\PluginBundle\Integration\AbstractIntegration;

/**
 * Class PluginIntegrationFormDisplayEvent.
 */
class PluginIntegrationFormDisplayEvent extends AbstractPluginIntegrationEvent
{
    /**
     * @var string
     */
    private $settings = [];

    /**
     * PluginIntegrationAuthCallbackUrlEvent constructor.
     *
     * @param AbstractIntegration $integration
     * @param                     $settings
     */
    public function __construct(AbstractIntegration $integration, array $settings)
    {
        $this->integration = $integration;
        $this->settings    = $settings;
    }

    /**
     * @return array
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * @param array $settings
     */
    public function setSettings(array $settings)
    {
        $this->settings = $settings;

        $this->stopPropagation();
    }
}
