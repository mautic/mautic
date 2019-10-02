<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic Inc.
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Integration;

use MauticPlugin\IntegrationsBundle\Integration\BC\BcIntegrationSettingsTrait;
use MauticPlugin\IntegrationsBundle\Integration\Interfaces\IntegrationInterface;

abstract class BasicIntegration implements IntegrationInterface
{
    use BcIntegrationSettingsTrait;
    use ConfigurationTrait;

    /**
     * @return string
     */
    public function getDisplayName(): string
    {
        return $this->getName();
    }
}
