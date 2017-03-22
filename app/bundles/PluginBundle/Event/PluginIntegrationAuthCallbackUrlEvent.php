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
 * Class PluginIntegrationAuthCallbackUrlEvent.
 */
class PluginIntegrationAuthCallbackUrlEvent extends AbstractPluginIntegrationEvent
{
    /**
     * @var string
     */
    private $callbackUrl;

    /**
     * PluginIntegrationAuthCallbackUrlEvent constructor.
     *
     * @param AbstractIntegration $integration
     * @param                     $callbackUrl
     */
    public function __construct(AbstractIntegration $integration, $callbackUrl)
    {
        $this->integration = $integration;
        $this->callbackUrl = $callbackUrl;
    }

    /**
     * @return string
     */
    public function getCallbackUrl()
    {
        return $this->callbackUrl;
    }

    /**
     * @param string $callbackUrl
     */
    public function setCallbackUrl($callbackUrl)
    {
        $this->callbackUrl = $callbackUrl;

        $this->stopPropagation();
    }
}
