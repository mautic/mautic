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


interface ConfigFormFeaturesInterface
{
    /**
     * Return the name of the form type service for the enabled features tab
     *
     * @return string|null
     */
    public function getFeaturesConfigFormName(): ?string;

    /**
     * Return an array of value => label pairs for the features this integration supports
     *
     * @return array
     */
    public function getSupportedFeatures(): array;
}