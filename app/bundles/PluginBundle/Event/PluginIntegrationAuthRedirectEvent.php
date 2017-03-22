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
 * Class PluginIntegrationAuthRedirectEvent.
 */
class PluginIntegrationAuthRedirectEvent extends AbstractPluginIntegrationEvent
{
    /**
     * @var string
     */
    private $authUrl;

    /**
     * PluginIntegrationAuthRedirectEvent constructor.
     *
     * @param AbstractIntegration $integration
     * @param                     $authUrl
     */
    public function __construct(AbstractIntegration $integration, $authUrl)
    {
        $this->integration = $integration;
        $this->authUrl     = $authUrl;
    }

    /**
     * @return string
     */
    public function getAuthUrl()
    {
        return $this->authUrl;
    }

    /**
     * @param string $authUrl
     */
    public function setAuthUrl($authUrl)
    {
        $this->authUrl = $authUrl;

        $this->stopPropagation();
    }
}
