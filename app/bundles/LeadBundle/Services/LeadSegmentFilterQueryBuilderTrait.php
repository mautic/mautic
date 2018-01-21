<?php
/**
 * Created by PhpStorm.
 * User: jan
 * Date: 1/9/18
 * Time: 1:54 PM.
 */

namespace Mautic\LeadBundle\Services;

trait LeadSegmentFilterQueryBuilderTrait
{
    // @todo make static to asure single instance
    protected $parameterAliases = [];

    /**
     * Generate a unique parameter name.
     *
     * @todo make use of the service, this is VERY unreliable
     *
     * @return string
     */
    protected function generateRandomParameterName()
    {
        throw new \Exception('This function is obsole, remove references to it.');
    }
}
