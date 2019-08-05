<?php

/*
 * @copyright   2019 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Integration\Interfaces;

interface ConfigFormCallbackInterface
{
    public const CONFIG_KEY_CALLBACK_URL = 'callback_url';

    /**
     * @return string
     */
    public function getCallbackUrl(): string;
}