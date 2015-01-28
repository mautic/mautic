<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticAddon\MauticEmailMarketingBundle\Api;

use Mautic\AddonBundle\Integration\AbstractIntegration;

class EmailMarketingApi {

    protected $integration;
    protected $keys;

    /**
     * @param AbstractIntegration $integration
     */
    public function __construct (AbstractIntegration $integration)
    {
        $this->integration = $integration;
        $this->keys        = $integration->getKeys();
    }
}