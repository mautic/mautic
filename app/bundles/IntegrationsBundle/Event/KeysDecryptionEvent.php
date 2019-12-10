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

class KeysDecryptionEvent extends Event
{
    /**
     * @var Integration
     */
    private $integrationConfiguration;

    /**
     * @var array
     */
    private $keys;

    /**
     * KeysEncryptionEvent constructor.
     *
     * @param Integration $integrationConfiguration
     * @param array       $keys
     */
    public function __construct(Integration $integrationConfiguration, array $keys)
    {
        $this->integrationConfiguration = $integrationConfiguration;
        $this->keys                     = $keys;
    }

    /**
     * @return Integration
     */
    public function getIntegrationConfiguration(): Integration
    {
        return $this->integrationConfiguration;
    }

    /**
     * @return array
     */
    public function getKeys(): array
    {
        return $this->keys;
    }

    /**
     * @param array $keys
     */
    public function setKeys(array $keys): void
    {
        $this->keys = $keys;
    }
}
