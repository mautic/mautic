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
     * @return string
     */
    protected function generateRandomParameterName()
    {
        $alpha_numeric = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

        $paramName = substr(str_shuffle($alpha_numeric), 0, 8);

        if (!in_array($paramName, $this->parameterAliases)) {
            $this->parameterAliases[] = $paramName;

            return $paramName;
        }

        return $this->generateRandomParameterName();
    }
}
