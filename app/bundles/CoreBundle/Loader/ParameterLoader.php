<?php

namespace Mautic\CoreBundle\Loader;

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\ParameterBag;

final class ParameterLoader
{
    private readonly string $configBaseDir;

    private ParameterBag $parameterBag;

    private ParameterBag $localParameterBag;

    /**
     * @var array<string, mixed>
     */
    private array $localParameters;

    /**
     * @var array<string, mixed>
     */
    private static array $defaultParameters = [];

    public function __construct(
        private readonly string $rootPath = __DIR__.'/../../../',
    ) {
        $this->configBaseDir = self::getLocalConfigBaseDir($this->rootPath);

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
        $root        = realpath($root);
        $projectRoot = self::getProjectDirByRoot($root);

        /** @var array<string> $paths */
        $paths = [];
        include $root.'/config/paths.php';

        if (!isset($paths['local_config'])) {
            if ($updateDefaultParameters) {
                self::$defaultParameters['local_config_path'] = $projectRoot.'/config/local.php';
            }

            return $projectRoot.'/config/local.php';
        }

        $newConfigPath = str_replace('%kernel.project_dir%', $projectRoot, $paths['local_config']);
        $oldConfigPath = str_replace('%kernel.project_dir%', $root, $paths['local_config']);

        $paths['local_config'] = $newConfigPath;

        // Check if the local config files are still present in the /app/config dir, instead of the /config dir
        if (!file_exists($newConfigPath) && file_exists($oldConfigPath)) {
            $paths['local_config'] = $oldConfigPath;
        }

        if ($updateDefaultParameters) {
            self::$defaultParameters['local_config_path'] = $paths['local_config'];
        }

        // We need this for the file manager (ElFinder) and other webroot-relative paths.
        // If local_root is explicitly set in paths_local.php, use that.
        // Otherwise, auto-detect from composer.json's mautic-scaffold.locations.web-root
        // or extra.public-dir for recommended-project installations.
        if (isset($paths['local_root'])) {
            if ($updateDefaultParameters) {
                self::$defaultParameters['local_root'] = str_replace('%kernel.project_dir%', $projectRoot, $paths['local_root']);
            }
        } elseif ($updateDefaultParameters) {
            $webrootDir = self::getWebrootDir($projectRoot);
            if ($webrootDir !== $projectRoot) {
                self::$defaultParameters['local_root'] = $webrootDir;
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
            include $localParametersFile;
            /** @var array<string, mixed> $parameters */

            // override default with forced
            $compiledParameters = array_merge($compiledParameters, $parameters);
        }

        // Load from environment
        $envParameters = getenv('MAUTIC_CONFIG_PARAMETERS');
        if ($envParameters) {
            $compiledParameters = array_merge($compiledParameters, json_decode($envParameters, true));
        }

        // Hardcode the db_driver to pdo_mysql, as it is currently the only supported driver.
        // We set in here, to ensure it is always set to this value.
        $compiledParameters['db_driver'] = 'pdo_mysql';

        $this->localParameters = $compiledParameters;
    }

    private function createParameterBags(): void
    {
        $this->localParameterBag = new ParameterBag($this->localParameters);
        $this->parameterBag      = new ParameterBag(array_merge(self::$defaultParameters, $this->localParameters));
    }

    private function getLocalParametersFile(): string
    {
        // load the local parameter file from the same dir as the local config file.
        return $this->configBaseDir.'/config/parameters_local.php';
    }

    public static function getLocalConfigBaseDir(string $root): string
    {
        $rootDir         = Path::canonicalize($root);
        $projectDir      = self::getProjectDirByRoot($rootDir);
        $localConfigFile = self::getLocalConfigFile($root, false);

        if (Path::isBasePath($rootDir, $localConfigFile)) {
            return $rootDir;
        }
        if (Path::isBasePath($projectDir, $localConfigFile)) {
            return $projectDir;
        }

        return $root;
    }

    public static function getProjectDirByRoot(string $root): string
    {
        $dir = $rootDir = \dirname($root, 1);
        while (!is_file($dir.'/composer.json')) {
            if ($dir === \dirname($dir)) {
                return $rootDir;
            }
            $dir = \dirname($dir);
        }

        return $dir;
    }

    /**
     * Detects the webroot directory from composer.json configuration.
     *
     * Checks for mautic-scaffold.locations.web-root (used by recommended-project)
     * or Symfony's extra.public-dir. Returns the project root if no subdirectory
     * webroot is configured.
     */
    public static function getWebrootDir(string $projectRoot): string
    {
        $composerFile = $projectRoot.'/composer.json';
        if (!file_exists($composerFile)) {
            return $projectRoot;
        }

        $composerContent = file_get_contents($composerFile);
        if (false === $composerContent) {
            return $projectRoot;
        }

        $composerJson = json_decode($composerContent, true);
        if (!is_array($composerJson)) {
            return $projectRoot;
        }

        // Check mautic-scaffold.locations.web-root (used by recommended-project)
        $webRoot = $composerJson['extra']['mautic-scaffold']['locations']['web-root'] ?? null;

        // Fallback to Symfony's public-dir
        if (null === $webRoot) {
            $webRoot = $composerJson['extra']['public-dir'] ?? '.';
        }

        $webRoot = rtrim($webRoot, '/');
        if ('.' === $webRoot || '' === $webRoot) {
            return $projectRoot;
        }

        $webrootPath = $projectRoot.'/'.$webRoot;

        return is_dir($webrootPath) ? $webrootPath : $projectRoot;
    }
}
