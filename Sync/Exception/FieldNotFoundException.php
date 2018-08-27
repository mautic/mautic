<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Sync\Exception;


class FieldNotFoundException extends \Exception
{
    /**
     * FieldNotFoundException constructor.
     *
     * @param string $field
     * @param string $internalObject
     * @param string $integrationObject
     */
    public function __construct(string $field, string $internalObject, string $integrationObject)
    {
        parent::__construct("$field was not found in the mapping for $internalObject:$integrationObject");
    }
}