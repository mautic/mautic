<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticIntegrationsBundle\Event;

use DateTimeImmutable;
use Mautic\PluginBundle\Entity\Integration;
use Symfony\Component\EventDispatcher\Event;

class SyncEvent extends Event
{
    /**
     * @var string
     */
    private $integration;

    /**
     * @var DateTimeImmutable
     */
    private $startDate;

    /**
     * @param string            $integration
     * @param DateTimeImmutable $startDate
     */
    public function __construct($integration, DateTimeImmutable $startDate)
    {
        $this->integration = $integration;
        $this->startDate   = $startDate;
    }

    /**
     * @return bool
     */
    public function shouldIntegrationSync($integration): bool
    {
        return strtolower($this->integration) === strtolower($integration);
    }

    /**
     * @return DateTimeImmutable
     */
    public function getStartDate()
    {
        return $this->startDate;
    }
}
