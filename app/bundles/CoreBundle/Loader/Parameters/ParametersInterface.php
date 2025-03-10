<?php

namespace Mautic\CoreBundle\Loader\Parameters;

interface ParametersInterface
{
    /**
     * @param array<mixed, mixed>  $compiledParameters Mautic local parameters
     * @param array<string, mixed> $defaultParameters  Bundle and plugin's default parameters
     */
    public static function load(array &$compiledParameters, array $defaultParameters): void;
}
