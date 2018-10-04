<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;

/**
 * Class SyncEvent
 *
 * @package MauticPlugin\IntegrationsBundle\Event
 */
class SyncEvent extends CommonEvent
{
    /** @var string */
    private $integrationName;

    public function __construct(string $integrationName)
    {
        $this->integrationName = $integrationName;
    }

    /**
     * @return string
     */
    public function getIntegrationName(): string
    {
        return $this->integrationName;
    }

    /**
     * @param string $integrationName
     *
     * @return bool
     */
    public function isIntegration(string $integrationName): bool {
        return $this->getIntegrationName() === $integrationName;
    }
}
