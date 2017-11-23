<?php
/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      WebMecanik
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticWebinarBundle\Api;

use MauticPlugin\MauticWebinarBundle\Integration\WebinarAbstractIntegration;

/**
 * Class WebinarApi.
 */
class WebinarApi
{
    protected $integration;

    public function __construct(WebinarAbstractIntegration $integration)
    {
        $this->integration = $integration;
    }
}
