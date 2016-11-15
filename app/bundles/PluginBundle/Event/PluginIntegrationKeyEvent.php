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
 * Class PluginIntegrationKeyEvent.
 */
class PluginIntegrationKeyEvent extends AbstractPluginIntegrationEvent
{
    /**
     * @var array
     */
    private $keys;

    /**
     * PluginIntegrationKeyEvent constructor.
     *
     * @param AbstractIntegration $integration
     * @param array|null          $keys
     */
    public function __construct(AbstractIntegration $integration, array $keys = null)
    {
        $this->integration = $integration;
        $this->keys        = $keys;
    }

    /**
     * Get the keys array.
     */
    public function getKeys()
    {
        return $this->keys;
    }

    /**
     * Set new keys array.
     *
     * @param $keys
     */
    public function setKeys(array $keys)
    {
        $this->keys = $keys;
    }
}
