<?php

/*
 * @copyright   2018 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Integration\Interfaces;


interface ConfigFormInterface
{
    /**
     * @return string
     */
    public function getDisplayName(): string;

    /**
     * Override default config form with something custom
     *
     * @return string Name of the form type service
     */
    public function getConfigFormName(): string;
}