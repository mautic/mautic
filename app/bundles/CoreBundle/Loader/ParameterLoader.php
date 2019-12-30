<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Loader;

use Symfony\Component\HttpFoundation\ParameterBag;

class ParameterLoader
{
    /**
     * @var ParameterBag
     */
    private static $parameterBag;

    /**
     * @var array
     */
    private $defaultParameters = [];

    public function __construct(array $defaultParameters = [])
    {
        // This is loaded outside the container and inside the container so statically store to prevent
        // having to recompile multiple times
        if (self::$parameterBag) {
            return;
        }

        $this->loadDefaultParameters($defaultParameters);
        $this->loadLocalParameters();
    }

    public function getParameterBag(): ParameterBag
    {
        return self::$parameterBag;
    }

    public function loadIntoEnvironment()
    {
        $envVariables      = new ParameterBag();
        $defaultParameters = new ParameterBag($this->defaultParameters);

        // Load from configuration file first
        EnvVars\ConfigEnvVars::load(self::$parameterBag, $defaultParameters, $envVariables);

        // Load the others
        EnvVars\ApiEnvVars::load(self::$parameterBag, $defaultParameters, $envVariables);
        EnvVars\LogEnvVars::load(self::$parameterBag, $defaultParameters, $envVariables);
        EnvVars\MigrationsEnvVars::load(self::$parameterBag, $defaultParameters, $envVariables);
        EnvVars\SAMLEnvVars::load(self::$parameterBag, $defaultParameters, $envVariables);
        EnvVars\SessionEnvVars::load(self::$parameterBag, $defaultParameters, $envVariables);
        EnvVars\SiteUrlEnvVars::load(self::$parameterBag, $defaultParameters, $envVariables);
        EnvVars\TwigEnvVars::load(self::$parameterBag, $defaultParameters, $envVariables);

        // Load the values into the environment for cache use
        $dotenv = new \Symfony\Component\Dotenv\Dotenv();
        $dotenv->populate($envVariables->all());
    }

    public static function getLocalConfigFile(string $root): string
    {
        $root = realpath($root);

        /** @var array $paths */
        include $root.'/config/paths.php';

        if (!isset($paths['local_config'])) {
            return $root.'/config/local.php';
        }

        $paths['local_config'] = str_replace('%kernel.root_dir%', $root, $paths['local_config']);

        defined('MAUTIC_LOCAL_CONFIG_FILE') or define('MAUTIC_LOCAL_CONFIG_FILE', $paths['local_config']);

        return $paths['local_config'];
    }

    private function loadDefaultParameters(array $defaultParameters): void
    {
        $defaultParametersFile = __DIR__.'/../../../config/parameters_defaults.php';

        if ($defaultParameters) {
            unset($defaultParameters['paths']);

            // Write defaults to a file so that they can be reloaded by the kernel without recompiling from each bundle's config file
            file_put_contents($defaultParametersFile, '<?php $defaultParameters = '.var_export($defaultParameters, true).';');

            $this->defaultParameters = $defaultParameters;

            return;
        }

        if (file_exists($defaultParametersFile)) {
            include $defaultParametersFile;
        }

        $this->defaultParameters = $defaultParameters;
    }

    private function loadLocalParameters(): void
    {
        $compiledParameters = $this->defaultParameters;
        $rootPath           = __DIR__.'/../../../';
        $localConfigFile    = self::getLocalConfigFile($rootPath);

        // Load parameters array from local configuration
        if (file_exists($localConfigFile)) {
            /** @var array $parameters */
            include $localConfigFile;

            // Override default with local
            $compiledParameters = array_merge($compiledParameters, $parameters);
        }

        // Force local specific params
        if (file_exists($rootPath.'/config/parameters_local.php')) {
            /** @var array $parameters */
            include $rootPath.'/config/parameters_local.php';

            //override default with forced
            $compiledParameters = array_merge($compiledParameters, $parameters);
        }

        self::$parameterBag = new ParameterBag($compiledParameters);
    }
}
