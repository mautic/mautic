<?php

namespace Mautic\CoreBundle\Loader\Parameters;

class EnvVarParameters implements ParametersInterface
{
    /**
     * @param array<mixed, mixed>  $compiledParameters
     * @param array<string, mixed> $defaultParameters
     */
    public static function load(array &$compiledParameters, array $defaultParameters): void
    {
        // Overrule parameters via environment variable
        $envParameters = getenv('MAUTIC_CONFIG_PARAMETERS') ?: $_ENV['MAUTIC_CONFIG_PARAMETERS'] ?? false;
        if ($envParameters) {
            $compiledParameters = array_merge($compiledParameters, json_decode($envParameters, true));
        }

        // Resolve parameters from environment variables
        foreach ($compiledParameters as $key => $value) {
            if (!empty($value) && is_string($value) && str_starts_with($value, 'getenv(') && preg_match('/getenv\((.*?)\)/', $value, $match)) {
                $value = (string) getenv($match[1]) ?: $_ENV[$match[1]] ?? '';

                // JSON decode arrays
                if (is_array($defaultParameters[$key] ?? null)) {
                    $value = json_decode($value, true);
                }

                $compiledParameters[$key] = $value;
            }
        }
    }
}
