<?php

class MauticParameterImporter
{
    private $parameters        = [];
    private $defaultParameters = [];

    public function __construct(string $localConfigFile, array $paths, array $defaultParameters = [])
    {
        $defaultFile = __DIR__.'/parameters_defaults.php';
        if ($defaultParameters) {
            unset($defaultParameters['paths']);

            // write defaults to a file so that they can be reloaded by the kernel
            file_put_contents($defaultFile, '<?php $defaultParameters = '.var_export($defaultParameters, true).';');
        } elseif (file_exists($defaultFile)) {
            include $defaultFile;
        }

        $this->defaultParameters = $defaultParameters;
        $compiledParameters      = $defaultParameters;

        // Load parameters array from local configuration
        if (file_exists($localConfigFile)) {
            /** @var array $parameters */
            include $localConfigFile;

            // Override default with local
            $compiledParameters = array_merge($compiledParameters, $parameters);
        }

        // Force local specific params
        if (file_exists(__DIR__.'/parameters_local.php')) {
            include __DIR__.'/parameters_local.php';

            //override default with forced
            $compiledParameters = array_merge($compiledParameters, $parameters);
        }

        // Store compiled local config path
        $paths['local_config']       = $localConfigFile;
        $compiledParameters['paths'] = $paths;

        $this->parameters = $compiledParameters;
    }

    public function has(string $key): bool
    {
        return isset($this->parameters[$key]);
    }

    public function get(string $key)
    {
        return $this->parameters[$key] ?? null;
    }

    public function all(): array
    {
        return $this->parameters;
    }

    public function loadIntoEnvironment()
    {
        $envVariables = [];
        foreach ($this->parameters as $k => $v) {
            if (!empty($v) && is_string($v) && preg_match('/getenv\((.*?)\)/', $v, $match)) {
                $v = (string) getenv($match[1]);
            }

            $key = sprintf('MAUTIC_%s', mb_strtoupper($k));

            $defaultValue = $this->defaultParameters[$k] ?? $v;
            if (is_array($v) || is_array($defaultValue)) {
                $jsonValue = $v ? $v : $defaultValue;
                $v         = json_encode($jsonValue);
            }

            switch ($k) {
                case 'api_oauth2_access_token_lifetime':
                    $v = is_int($v) ? $v * 60 : 3600;
                    break;
                case 'api_oauth2_refresh_token_lifetime':
                    $v = is_int($v) ? $v * 60 * 60 * 24 : 1209600;
                    break;
            }

            $envVariables[$key] = $v;
        }

        // Set the values required for the request
        $entityId = 'mautic';

        if (!empty($this->parameters['site_url'])) {
            $parts = parse_url($this->parameters['site_url']);

            if (!empty($parts['host'])) {
                $envVariables['MAUTIC_REQUEST_CONTEXT_HOST']   = $parts['host'];
                $envVariables['MAUTIC_REQUEST_CONTEXT_SCHEME'] = (!empty($parts['scheme']) ? $parts['scheme'] : 'http');

                $path = '';
                if (!empty($parts['path'])) {
                    // Check and remove trailing slash to prevent double // in Symfony cli generated URLs
                    $path = $parts['path'];
                    if ('/' == substr($path, -1)) {
                        $path = substr($path, 0, -1);
                    }
                }
                $envVariables['MAUTIC_REQUEST_CONTEXT_BASE_URL'] = $path;

                if (!empty($parts['port'])) {
                    $envVariables['MAUTIC_REQUEST_CONTEXT_PORT'] = !empty($parts['port']) ? $parts['port'] : null;
                }

                $entityId = $envVariables['MAUTIC_REQUEST_CONTEXT_SCHEME'].'://'.$envVariables['MAUTIC_REQUEST_CONTEXT_HOST'];
            }

            $envVariables['MAUTIC_SAML_ENTITY_ID'] = $entityId;
            // Update the file paths in case $factory->getParameter() is used
            //$replaceRootPlaceholder($v);
        }

        // Load the values into the environment for cache use
        $dotenv = new \Symfony\Component\Dotenv\Dotenv();
        $dotenv->populate($envVariables);
    }
}
