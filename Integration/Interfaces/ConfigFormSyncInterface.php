<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Integration\Interfaces;

use MauticPlugin\IntegrationsBundle\Mapping\MappedFieldInfoInterface;

interface ConfigFormSyncInterface extends IntegrationInterface
{
    /**
     * Return an array of Integration objects in the format of [$object => $translatableObjectNameString].
     * i.e. ['Customer' => 'mautic.something.object.customer', 'Account' => 'mautic.something.object.account'];.
     *
     * @return array
     */
    public function getSyncConfigObjects(): array;

    /**
     * Return an array of Integration objects and what Mautic objects they are mapped to.
     * i.e. ['Customer' => Contact::NAME, 'Account' =>  Company::NAME];.
     *
     * @return array
     */
    public function getSyncMappedObjects(): array;

    /**
     * Return an array of required fields.
     *
     * @param string $object
     *
     * @return MappedFieldInfoInterface[]
     */
    public function getRequiredFieldsForMapping(string $object): array;

    /**
     * Return an array of optional fields.
     *
     * @param string $object
     *
     * @return MappedFieldInfoInterface[]
     */
    public function getOptionalFieldsForMapping(string $object): array;

    /**
     * Return an array of all fields.
     *
     * @param string $object
     *
     * @return MappedFieldInfoInterface[]
     */
    public function getAllFieldsForMapping(string $object): array;

    /**
     * Return a custom form field name to be included in the features array specific to sync.
     *
     * @return string|null
     */
    public function getSyncConfigFormName(): ?string;
}
