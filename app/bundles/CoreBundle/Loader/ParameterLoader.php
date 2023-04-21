<?php

namespace Mautic\CoreBundle\Loader;

use Mautic\MessengerBundle\Loader\EnvVars\MessengerEnvLoader;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\ParameterBag;

class ParameterLoader
{
    /**
     * @var string
     */
    private $rootPath;

    /**
     * @var ParameterBag
     */
    private $parameterBag;

    /**
     * @var ParameterBag
     */
    private $localParameterBag;

    /**
     * @var array<string, mixed>
     */
    private $localParameters = [];

    /**
     * @var array<string, mixed>
     */
    private static $defaultParameters = [];

    public function __construct(string $configRootPath = __DIR__.'/../../../')
    {
        $this->rootPath = $configRootPath;

        $this->loadDefaultParameters();
        $this->loadLocalParameters();
        $this->createParameterBags();
    }

    /**
     * @return array<string, mixed>
     */
    public function getDefaultParameters(): array
    {
        return self::$defaultParameters;
    }

    public function getParameterBag(): ParameterBag
    {
        return $this->parameterBag;
    }

    public function getLocalParameterBag(): ParameterBag
    {
        return $this->localParameterBag;
    }

    public function loadIntoEnvironment(): void
    {
        $envVariables      = new ParameterBag();
        $defaultParameters = new ParameterBag(self::$defaultParameters);

        // Load from local configuration file first
        EnvVars\ConfigEnvVars::load($this->parameterBag, $defaultParameters, $envVariables);

        // Load special values used in Mautic configuration files in app/config
        EnvVars\ApiEnvVars::load($this->parameterBag, $defaultParameters, $envVariables);
        EnvVars\ElFinderEnvVars::load($this->parameterBag, $defaultParameters, $envVariables);
        EnvVars\MigrationsEnvVars::load($this->parameterBag, $defaultParameters, $envVariables);
        EnvVars\SAMLEnvVars::load($this->parameterBag, $defaultParameters, $envVariables);
        EnvVars\SessionEnvVars::load($this->parameterBag, $defaultParameters, $envVariables);
        EnvVars\SiteUrlEnvVars::load($this->parameterBag, $defaultParameters, $envVariables);
        EnvVars\TwigEnvVars::load($this->parameterBag, $defaultParameters, $envVariables);
        MessengerEnvLoader::load($this->parameterBag, $defaultParameters, $envVariables);

        // Load the values into the environment for cache use
        $dotenv = new Dotenv(MAUTIC_ENV);
        foreach ($envVariables->all() as $key => $value) {
            if (null === $value) {
                $envVariables->set($key, '');
            }
        }
        $dotenv->populate($envVariables->all());
    }

    public static function getLocalConfigFile(string $root, bool $updateDefaultParameters = true): string
    {
        $root = realpath($root);

        /** @var array<string> $paths */
        $paths = [];
        include $root.'/config/paths.php';

        if (!isset($paths['local_config'])) {
            if ($updateDefaultParameters) {
                self::$defaultParameters['local_config_path'] = $root.'/config/local.php';
            }

            return $root.'/config/local.php';
        }

        $paths['local_config'] = str_replace('%kernel.project_dir%', $root.'/..', $paths['local_config']);

        if ($updateDefaultParameters) {
            self::$defaultParameters['local_config_path'] = $paths['local_config'];
        }

        // We need this for the file manager
        if (isset($paths['local_root'])) {
            if ($updateDefaultParameters) {
                self::$defaultParameters['local_root'] = $paths['local_root'];
            }
        }

        return $paths['local_config'];
    }

    private function loadDefaultParameters(): void
    {
        if (self::$defaultParameters) {
            // This is loaded within and outside the container so use static variable to prevent recompiling
            // multiple times
            return;
        }

        $finder = (new Finder())
            ->files()
            ->followLinks()
            ->depth('== 0')
            ->in(__DIR__.'/../../../bundles/*/Config')
            ->in(__DIR__.'/../../../../plugins/*/Config')
            ->name('config.php');

        /** @var \SplFileInfo $file */
        foreach ($finder as $file) {
            /** @var array<string, mixed> $config */
            $config = include $file->getPathname();

            $parameters              = $config['parameters'] ?? [];
            self::$defaultParameters = array_merge(self::$defaultParameters, $parameters);
        }
    }

    private function loadLocalParameters(): void
    {
        $compiledParameters = [];
        $localConfigFile    = self::getLocalConfigFile($this->rootPath);

        // Load parameters array from local configuration
        if (file_exists($localConfigFile)) {
            /** @var array<string, mixed> $parameters */
            $parameters = [];
            include $localConfigFile;

            // Override default with local
            $compiledParameters = array_merge($compiledParameters, $parameters);
        }

        // Force local specific params
        $localParametersFile = $this->getLocalParametersFile();
        if (file_exists($localParametersFile)) {
            /** @var array<string, mixed> $parameters */
            include $localParametersFile;

            //override default with forced
            $compiledParameters = array_merge($compiledParameters, $parameters);
        }

        // Load from environment
        $envParameters = getenv('MAUTIC_CONFIG_PARAMETERS');
        if ($envParameters) {
            $compiledParameters = array_merge($compiledParameters, json_decode($envParameters, true));
        }

        $this->localParameters = $compiledParameters;
    }

    private function createParameterBags(): void
    {
        $this->localParameterBag = new ParameterBag($this->localParameters);
        $this->parameterBag      = new ParameterBag(array_merge(self::$defaultParameters, $this->localParameters));
    }

    private function getLocalParametersFile(): string
    {
        return $this->rootPath.'/config/parameters_local.php';
    }
}
