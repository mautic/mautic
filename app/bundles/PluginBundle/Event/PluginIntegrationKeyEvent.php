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
 * Class PluginIntegrationKeyEvent
 */
class PluginIntegrationKeyEvent extends Event
{
    /**
     * @var AbstractIntegration
     */
    private $integration;

    /**
     * @var array
     */
    private $keys;

    public function __construct(AbstractIntegration $integration, array $keys = null)
    {
        $this->integration = $integration;
        $this->keys        = $keys;
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
     * Get the keys array
     */
    public function getKeys()
    {
        return $this->keys;
    }

    /**
     * Set new keys array
     *
     * @param $keys
     */
    public function setKeys(array $keys)
    {
        $this->keys = $keys;
    }
}
