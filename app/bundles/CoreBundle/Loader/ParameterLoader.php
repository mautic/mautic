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

    /**
     * Keys this loader has itself populated into the environment during this process, tracked so
     * a later call (e.g. re-booting the kernel between tests within the same PHP process) can
     * still refresh values it previously set. Without this, that protection would incorrectly
     * extend to values that were never set by this method, e.g. a value from a .env/.env.local
     * file loaded once during the application's real bootstrap.
     *
     * @var array<string, true>
     */
    private static array $selfPopulatedEnvKeys = [];

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

            // Do not let a value derived from local.php override a value that has already been
            // set in the environment - unless we are the ones who set it. This protects a value
            // that came from a real system/OS environment variable or from a .env/.env.local file
            // loaded earlier during the application's bootstrap. Dotenv::populate() only skips
            // values that are *not* tracked in $_ENV['SYMFONY_DOTENV_VARS'], so a value loaded
            // from a .env file (which *is* tracked there) would otherwise still be silently
            // overwritten below, even though a plain system environment variable of the same name
            // would not be. Keying the guard off our own tracking (rather than only off
            // SYMFONY_DOTENV_VARS) also keeps a later call in the same process - e.g. a kernel
            // reboot between tests - free to refresh values it previously computed itself.
            if (!isset(self::$selfPopulatedEnvKeys[$key]) && isset($_ENV[$key])) {
                $envVariables->remove($key);
            }
        }
        $dotenv->populate($envVariables->all());

        foreach ($envVariables->all() as $key => $value) {
            self::$selfPopulatedEnvKeys[$key] = true;
        }
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
}
