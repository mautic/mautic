<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticFullContactBundle\Integration;

use Mautic\IntegrationsBundle\Integration\BasicIntegration;
use Mautic\IntegrationsBundle\Integration\ConfigurationTrait;
use Mautic\IntegrationsBundle\Integration\Interfaces\BasicInterface;

class FullContactIntegration extends BasicIntegration implements BasicInterface
{
    use ConfigurationTrait;

    public const NAME         = 'fullcontact';
    public const DISPLAY_NAME = 'Full Contact';

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getDisplayName(): string
    {
        return self::DISPLAY_NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getIcon(): string
    {
        return 'plugins/MauticFullContactBundle/Assets/img/fullcontact.png';
    }

    public function getAuthenticationType(): string
    {
        return 'none';
    }

    public function shouldAutoUpdate()
    {
        // @todo: "auto_update" is part of $apiKey variable. Move it along with
        // other data configuration.
        $featureSettings = $this->getIntegrationConfiguration();

        //return (isset($featureSettings['auto_update'])) ? (bool) $featureSettings['auto_update'] : false;
        return true;
    }
}
