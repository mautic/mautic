<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Event;

use Mautic\PluginBundle\Entity\Integration;
use Symfony\Component\EventDispatcher\Event;

class FormLoadEvent extends Event
{
    /**
     * @var Integration
     */
    private $integration;

    public function __construct(Integration $integration)
    {
        $this->integration = $integration;
    }

    /**
     * @return Integration
     */
    public function getIntegration(): Integration
    {
        return $this->integration;
    }

    public function getIntegrationName()
    {
        return $this->integration->getName();
    }
}
