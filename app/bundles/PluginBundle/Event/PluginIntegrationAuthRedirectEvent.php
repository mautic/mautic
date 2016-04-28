<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\Event;

use Mautic\PluginBundle\Integration\AbstractIntegration;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class PluginIntegrationAuthRedirectEvent
 */
class PluginIntegrationAuthRedirectEvent extends Event
{
    /**
     * @var AbstractIntegration
     */
    private $integration;

    /**
     * @var string
     */
    private $authUrl;

    public function __construct(AbstractIntegration $integration, $authUrl)
    {
        $this->integration = $integration;
        $this->authUrl     = $authUrl;
    }

    /**
     * Get the integration's name
     *
     * @return mixed
     */
    public function getIntegrationName()
    {
        return $this->getIntegrationName();
    }

    /**
     * Get the integration object
     *
     * @return AbstractIntegration
     */
    public function getIntegration()
    {
        return $this->integration;
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
    }
}
