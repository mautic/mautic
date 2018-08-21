<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationBundle\Sync\Duplicity;


use MauticPlugin\IntegrationsBundle\Sync\DAO\Mapping\MappingManualDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report\ObjectDAO;

class DuplicityFinder
{
    public function findMauticObject(MappingManualDAO $mappingManualDAO, string $internalObjectName, ObjectDAO $integrationObjectDAO)
    {
        // get unique identifier fields from Mautic,
        // check if the field is mapped
        // get the field from the integration object
        // search for a match and return unique identifier fields
        // if none are found, fall back to email
        $objectId = null;

        $internalObject =  new ObjectDAO($internalObjectName, $objectId);

        // set unique identifier fields for integration identification

        return $internalObject;
    }

    public function findIntegrationObject(string $integrationObjectName, ObjectDAO $internalObjectDAO)
    {
        // search integration entity table for matches
    }
}