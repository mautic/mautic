<?php

/*
 * @copyright   2019 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Auth\Provider;

use Mautic\PluginBundle\Entity\Integration;
use MauticPlugin\IntegrationsBundle\Helper\IntegrationsHelper;
class TokenPersistenceFactory
{
    /**
     * @var IntegrationsHelper
     */
    private $integrationsHelper;

    /**
     * @param IntegrationsHelper $integrationsHelper
     */
    public function __construct(IntegrationsHelper $integrationsHelper)
    {
        $this->integrationsHelper = $integrationsHelper;
    }

    /**
     * @param Integration $integration
     *
     * @return TokenPersistence
     */
    public function create(Integration $integration)
    {
        $tokenPersistence = new TokenPersistence($this->integrationsHelper);

        $tokenPersistence->setIntegration($integration);

        return $tokenPersistence;
    }
}