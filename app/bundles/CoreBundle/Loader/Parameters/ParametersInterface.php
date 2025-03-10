<?php

namespace Mautic\CoreBundle\Loader\Parameters;

interface ParametersInterface
{
    /**
     * @param array $compiledParameters Mautic local parameters
     * @param array $defaultParameters  Bundle and plugin's default parameters
     */
    public static function load(array &$compiledParameters, array $defaultParameters): void;
}
