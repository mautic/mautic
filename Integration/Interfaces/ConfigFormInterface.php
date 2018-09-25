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


interface ConfigFormInterface extends IntegrationInterface
{
    /**
     * @return string
     */
    public function getDisplayName(): string;

    /**
     * Return the name/class of the form type to override the default or just return NULL to use the default
     *
     * @return string|null Name of the form type service
     */
    public function getConfigFormName(): ?string;
}