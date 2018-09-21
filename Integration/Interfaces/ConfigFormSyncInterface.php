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

use MauticPlugin\IntegrationsBundle\Sync\DAO\Mapping\MappingManualDAO;

interface ConfigFormSyncInterface
{
    /**
     * Return an array of Integration objects in the format of [$object => $translatableObjectNameString].
     * i.e. ['Customer' => 'mautic.something.object.customer', 'Account' => 'mautic.something.object.account'];
     *
     * @return array
     */
    public function getSyncConfigObjects(): array;

    /**
     * Return a custom form field name to be included in the features array specific to sync
     *
     * @return string|null
     */
    public function getSyncConfigFormName(): ?string;

    /**
     * Return the MappingManual so the form knows what objects to map to what fields
     *
     * @return MappingManualDAO
     */
    public function getMappingManual(): MappingManualDAO;
}