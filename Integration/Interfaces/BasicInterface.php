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

interface BasicInterface
{
    /**
     * Return the integration's name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * @return string
     */
    public function getDisplayName(): string;
}