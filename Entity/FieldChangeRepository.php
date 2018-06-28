<?php

namespace MauticPlugin\MauticIntegrationsBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

class FieldChangeRepository extends CommonRepository
{
    /**
     * Will return array of data based on passed mapping parameters.
     * Does _not_ return array of FieldChange entities.
     * 
     * @TODO
     * 
     * @param $mapping
     * 
     * @return array
     */
    public function basedOnMapping($mapping)
    {

    }
}