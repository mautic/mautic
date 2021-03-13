<?php

declare(strict_types=1);

/*
 * @copyright   2021 Mautic contributors. All rights reserved
 * @author      Acquia
 *
 * @link        https://www.mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\IntegrationsBundle\Event;

use Mautic\PluginBundle\Entity\Integration;
use Symfony\Component\EventDispatcher\Event;

final class ConfigAuthUrlEvent extends Event
{
    /**
     * @var Integration
     */
    private $integrationConfiguration;

    /**
     * @var string
     */
    private $authUrl;

    public function __construct(Integration $integrationConfiguration, string $authUrl)
    {
        $this->integrationConfiguration = $integrationConfiguration;
        $this->authUrl                  = $authUrl;
    }

    public function getIntegrationConfiguration(): Integration
    {
        return $this->integrationConfiguration;
    }

    public function getIntegration(): string
    {
        return $this->integrationConfiguration->getName();
    }

    public function getAuthUrl(): string
    {
        return $this->authUrl;
    }

    public function setAuthUrl(string $authUrl): void
    {
        $this->authUrl = $authUrl;
    }
}
