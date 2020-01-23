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

use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\ParameterBag;

class ParameterLoader
{
    /**
     * @var ParameterBag
     */
    private static $parameterBag;

    /**
     * @var ParameterBag
     */
    private static $localParameterBag;

    /**
     * @var array
     */
    private $defaultParameters = [];

    /**
     * @var array
     */
    private $localParameters = [];

    public function __construct()
    {
        // This is loaded outside the container and inside the container so statically store to prevent
        // having to recompile multiple times
        if (self::$parameterBag) {
            return;
        }

        $this->loadDefaultParameters();
        $this->loadLocalParameters();
        $this->createParameterBags();
    }

    public function getParameterBag(): ParameterBag
    {
        return self::$parameterBag;
    }

    public function getLocalParameterBag(): ParameterBag
    {
        return self::$localParameterBag;
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

        $paths['local_config'] = str_replace(['%%kernel.root_dir%%', '%kernel.root_dir%'], $root, $paths['local_config']);

        defined('MAUTIC_LOCAL_CONFIG_FILE') or define('MAUTIC_LOCAL_CONFIG_FILE', $paths['local_config']);

        return $paths['local_config'];
    }

    private function loadDefaultParameters(): void
    {
        $finder = (new Finder())
            ->files()
            ->followLinks()
            ->depth('== 0')
            ->in(__DIR__.'/../../../bundles/*/Config')
            ->in(__DIR__.'/../../../../plugins/*/Config')
            ->name('config.php');

        /** @var \SplFileInfo $file */
        foreach ($finder as $file) {
            /** @var array $config */
            $config = include $file->getPathname();

            $parameters              = $config['parameters'] ?? [];
            $this->defaultParameters = array_merge($this->defaultParameters, $parameters);
        }
    }

    private function loadLocalParameters(): void
    {
        $compiledParameters = [];
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
        $localParametersFile = $this->getLocalParametersFile();
        if (file_exists($localParametersFile)) {
            /** @var array $parameters */
            include $localParametersFile;

            //override default with forced
            $compiledParameters = array_merge($compiledParameters, $parameters);
        }

        $this->localParameters = $compiledParameters;
    }

    private function createParameterBags(): void
    {
        self::$localParameterBag = new ParameterBag($this->localParameters);
        self::$parameterBag      = new ParameterBag(array_merge($this->defaultParameters, $this->localParameters));
    }

    private function getLocalParametersFile(): string
    {
        return __DIR__.'/../../../config/parameters_local.php';
    }
}
