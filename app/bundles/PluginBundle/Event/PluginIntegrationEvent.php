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

use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Integration\AbstractIntegration;

/**
 * Class PluginIntegrationEvent.
 */
class PluginIntegrationEvent extends AbstractPluginIntegrationEvent
{
    /**
     * PluginIntegrationEvent constructor.
     *
     * @param AbstractIntegration $integration
     */
    public function __construct(AbstractIntegration $integration)
    {
        $this->integration = $integration;
    }

    /**
     * @return Integration
     */
    public function getEntity()
    {
        return $this->integration->getIntegrationSettings();
    }

    /**
     * @param Integration $integration
     */
    public function setEntity(Integration $integration)
    {
        $this->integration->setIntegrationSettings($integration);
    }
}
