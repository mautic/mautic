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
use Symfony\Component\EventDispatcher\Event;

/**
 * Class AbstractPluginIntegrationEvent.
 */
class AbstractPluginIntegrationEvent extends Event
{
    /**
     * @var AbstractIntegration
     */
    protected $integration;

    /**
     * Get the integration's name.
     *
     * @return mixed
     */
    public function getIntegrationName()
    {
        return $this->integration->getName();
    }

    /**
     * Get the integration object.
     *
     * @return AbstractIntegration
     */
    public function getIntegration()
    {
        return $this->integration;
    }
}
