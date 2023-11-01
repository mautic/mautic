<?php

namespace MauticPlugin\MauticFullContactBundle\Services;

/**
 * This class handles all the Location information.
 *
 * @author   Keith Casey <contrib@caseysoftware.com>
 * @license  http://www.apache.org/licenses/LICENSE-2.0 Apache
 */
class FullContact_Location extends FullContact_Base
{
    /**
     * Supported lookup methods.
     *
     * @var array
     */
    protected $_supportedMethods = ['normalizer', 'enrichment'];
    protected $_resourceUri      = '';

    /**
     * This takes a name and breaks it into its individual parts.
     *
     * @param type $name
     * @param type $casing -> valid values are uppercase, lowercase, titlecase
     *
     * @return type
     */
    public function normalizer($place, $includeZeroPopulation = false, $casing = 'titlecase')
    {
        $includeZeroPopulation = ($includeZeroPopulation) ? 'true' : 'false';

        $this->_resourceUri = '/address/locationNormalizer.json';
        $this->_execute(['place' => $place, 'includeZeroPopulation' => $includeZeroPopulation,
            'method'             => 'normalizer', 'casing' => $casing, ]);

        return $this->response_obj;
    }

    public function enrichment($place, $includeZeroPopulation = false, $casing = 'titlecase')
    {
        $includeZeroPopulation = ($includeZeroPopulation) ? 'true' : 'false';

        $this->_resourceUri = '/address/locationEnrichment.json';
        $this->_execute(['place' => $place, 'includeZeroPopulation' => $includeZeroPopulation,
            'method'             => 'enrichment', 'casing' => $casing, ]);

        return $this->response_obj;
    }
}
