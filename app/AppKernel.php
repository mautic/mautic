<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Config\EnvParametersResource;
use Symfony\Component\HttpKernel\DependencyInjection;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Mautic Application Kernel.
 */
class AppKernel extends Kernel
{
    /**
     * Major version number.
     *
     * @const integer
     */
    const MAJOR_VERSION = 2;

    /**
     * Minor version number.
     *
     * @const integer
     */
    const MINOR_VERSION = 8;

    /**
     * Patch version number.
     *
     * @const integer
     */
    const PATCH_VERSION = 1;

    /**
     * Extra version identifier.
     *
     * This constant is used to define additional version segments such as development
     * or beta status.
     *
     * @const string
     */
    const EXTRA_VERSION = '-dev';

    /**
     * @var array
     */
    private $pluginBundles = [];

    /**
     * Constructor.
     *
     * @param string $environment The environment
     * @param bool   $debug       Whether to enable debugging or not
     *
     * @api
     */
    public function __construct($environment, $debug)
    {
        defined('MAUTIC_ENV') or define('MAUTIC_ENV', $environment);
        defined('MAUTIC_VERSION') or define(
            'MAUTIC_VERSION',
            self::MAJOR_VERSION.'.'.self::MINOR_VERSION.'.'.self::PATCH_VERSION.self::EXTRA_VERSION
        );

        parent::__construct($environment, $debug);
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        if (strpos($request->getRequestUri(), 'installer') !== false || !$this->isInstalled()) {
            define('MAUTIC_INSTALLER', 1);
        }

        if (defined('MAUTIC_INSTALLER')) {
            $uri = $request->getRequestUri();
            if (strpos($uri, 'installer') === false) {
                $base = $request->getBaseUrl();
                //check to see if the .htaccess file exists or if not running under apache
                if ((strpos(strtolower($_SERVER['SERVER_SOFTWARE']), 'apache') === false
                    || !file_exists(__DIR__.'../.htaccess')
                    && strpos(
                        $base,
                        'index'
                    ) === false)
                ) {
                    $base .= '/index.php';
                }

                return new RedirectResponse($base.'/installer');
            }
        }

        if (false === $this->booted) {
            $this->boot();
        }

        // Check for an an active db connection and die with error if unable to connect
        if (!defined('MAUTIC_INSTALLER')) {
            $db = $this->getContainer()->get('database_connection');
            try {
                $db->connect();
            } catch (\Exception $e) {
                error_log($e);
                throw new \Mautic\CoreBundle\Exception\DatabaseConnectionException(
                    $this->getContainer()->get('translator')->trans(
                        'mautic.core.db.connection.error',
                        [
                            '%code%' => $e->getCode(),
                        ]
                    ),
                    0,
                    $e
                );
            }
        }

        return parent::handle($request, $type, $catch);
    }

    /**
     * {@inheritdoc}
     */
    public function registerBundles()
    {
        $bundles = [
            // Symfony/Core Bundles
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Doctrine\Bundle\DoctrineCacheBundle\DoctrineCacheBundle(),
            new Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle(),
            new Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle(),
            new Knp\Bundle\MenuBundle\KnpMenuBundle(),
            new FOS\OAuthServerBundle\FOSOAuthServerBundle(),
            new Bazinga\OAuthServerBundle\BazingaOAuthServerBundle(),
            new FOS\RestBundle\FOSRestBundle(),
            new JMS\SerializerBundle\JMSSerializerBundle(),
            new Oneup\UploaderBundle\OneupUploaderBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Debril\RssAtomBundle\DebrilRssAtomBundle(),
            // Mautic Bundles
            new Mautic\ApiBundle\MauticApiBundle(),
            new Mautic\AssetBundle\MauticAssetBundle(),
            new Mautic\CalendarBundle\MauticCalendarBundle(),
            new Mautic\CampaignBundle\MauticCampaignBundle(),
            new Mautic\CategoryBundle\MauticCategoryBundle(),
            new Mautic\ChannelBundle\MauticChannelBundle(),
            new Mautic\ConfigBundle\MauticConfigBundle(),
            new Mautic\CoreBundle\MauticCoreBundle(),
            new Mautic\DashboardBundle\MauticDashboardBundle(),
            new Mautic\DynamicContentBundle\MauticDynamicContentBundle(),
            new Mautic\EmailBundle\MauticEmailBundle(),
            new Mautic\FormBundle\MauticFormBundle(),
            new Mautic\InstallBundle\MauticInstallBundle(),
            new Mautic\LeadBundle\MauticLeadBundle(),
            new Mautic\NotificationBundle\MauticNotificationBundle(),
            new Mautic\PageBundle\MauticPageBundle(),
            new Mautic\PluginBundle\MauticPluginBundle(),
            new Mautic\PointBundle\MauticPointBundle(),
            new Mautic\ReportBundle\MauticReportBundle(),
            new Mautic\SmsBundle\MauticSmsBundle(),
            new Mautic\StageBundle\MauticStageBundle(),
            new Mautic\UserBundle\MauticUserBundle(),
            new Mautic\WebhookBundle\MauticWebhookBundle(),
            new LightSaml\SymfonyBridgeBundle\LightSamlSymfonyBridgeBundle(),
            new LightSaml\SpBundle\LightSamlSpBundle(),
            new Ivory\OrderedFormBundle\IvoryOrderedFormBundle(),
        ];

        //dynamically register Mautic Plugin Bundles
        $searchPath = dirname(__DIR__).'/plugins';
        $finder     = new \Symfony\Component\Finder\Finder();
        $finder->files()
            ->followLinks()
            ->depth('1')
            ->in($searchPath)
            ->name('*Bundle.php');

        foreach ($finder as $file) {
            $dirname  = basename($file->getRelativePath());
            $filename = substr($file->getFilename(), 0, -4);

            $class = '\\MauticPlugin'.'\\'.$dirname.'\\'.$filename;
            if (class_exists($class)) {
                $plugin = new $class();

                if ($plugin instanceof \Symfony\Component\HttpKernel\Bundle\Bundle) {
                    if (defined($class.'::MINIMUM_MAUTIC_VERSION')) {
                        // Check if this version supports the plugin before loading it
                        if (version_compare($this->getVersion(), constant($class.'::MINIMUM_MAUTIC_VERSION'), 'lt')) {
                            continue;
                        }
                    }
                    $bundles[] = $plugin;
                }

                unset($plugin);
            }
        }

        if (in_array($this->getEnvironment(), ['dev', 'test'])) {
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
            $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
            $bundles[] = new Webfactory\Bundle\ExceptionsBundle\WebfactoryExceptionsBundle();
        }

        if (in_array($this->getEnvironment(), ['test'])) {
            $bundles[] = new Liip\FunctionalTestBundle\LiipFunctionalTestBundle();
        }

        // Check for local bundle inclusion
        if (file_exists(__DIR__.'/config/bundles_local.php')) {
            include __DIR__.'/config/bundles_local.php';
        }

        return $bundles;
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        if (true === $this->booted) {
            return;
        }

        if (!defined('MAUTIC_TABLE_PREFIX')) {
            //set the table prefix before boot
            $localParams = $this->getLocalParams();
            $prefix      = isset($localParams['db_table_prefix']) ? $localParams['db_table_prefix'] : '';
            define('MAUTIC_TABLE_PREFIX', $prefix);
        }

        if ($this->loadClassCache) {
            $this->doLoadClassCache($this->loadClassCache[0], $this->loadClassCache[1]);
        }

        // init bundles
        $this->initializeBundles();

        // init container
        $this->initializeContainer();

        // If in console, set the table prefix since handle() is not executed
        if (defined('IN_MAUTIC_CONSOLE') && !defined('MAUTIC_TABLE_PREFIX')) {
            $localParams = $this->getLocalParams();
            $prefix      = isset($localParams['db_table_prefix']) ? $localParams['db_table_prefix'] : '';
            define('MAUTIC_TABLE_PREFIX', $prefix);
        }

        $registeredPluginBundles = $this->container->getParameter('mautic.plugin.bundles');

        foreach ($this->getBundles() as $name => $bundle) {
            $bundle->setContainer($this->container);
            $bundle->boot();
        }

        $this->pluginBundles = $registeredPluginBundles;

        $this->booted = true;
    }

    /**
     * Returns a list of addon bundles that are enabled.
     *
     * @return array
     */
    public function getPluginBundles()
    {
        return $this->pluginBundles;
    }

    /**
     * {@inheritdoc}
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config/config_'.$this->getEnvironment().'.php');
    }

    /**
     * Retrieves the application's version number.
     *
     * @return string
     */
    public function getVersion()
    {
        return MAUTIC_VERSION;
    }

    /**
     * Checks if the application has been installed.
     *
     * @return bool
     */
    private function isInstalled()
    {
        static $isInstalled = null;

        if ($isInstalled === null) {
            $params      = $this->getLocalParams();
            $isInstalled = (is_array($params) && !empty($params['db_driver']) && !empty($params['mailer_from_name']));
        }

        return $isInstalled;
    }

    /**
     * @param array $params
     *
     * @return \Doctrine\DBAL\Connection
     *
     * @throws Exception
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getDatabaseConnection($params = [])
    {
        if (empty($params)) {
            $params = $this->getLocalParams();
        }

        if (!empty($params) && !empty($params['db_driver'])) {
            $testParams = ['driver', 'host', 'port', 'name', 'user', 'password', 'path'];
            $dbParams   = [];
            foreach ($testParams as &$p) {
                $param = (isset($params["db_{$p}"])) ? $params["db_{$p}"] : '';
                if ($p == 'port') {
                    $param = (int) $param;
                }
                $name            = ($p == 'name') ? 'dbname' : $p;
                $dbParams[$name] = $param;
            }

            // Test a database connection and existence of a user
            $db = \Doctrine\DBAL\DriverManager::getConnection($dbParams);
            $db->connect();

            return $db;
        } else {
            throw new \Exception('not configured');
        }
    }

    /**
     * {@inheritdoc}
     *
     * @api
     */
    public function getCacheDir()
    {
        $parameters = $this->getLocalParams();
        if (isset($parameters['cache_path'])) {
            $envFolder = (strpos($parameters['cache_path'], -1) != '/') ? '/'.$this->environment : $this->environment;

            return str_replace('%kernel.root_dir%', $this->getRootDir(), $parameters['cache_path'].$envFolder);
        } else {
            return parent::getCacheDir();
        }
    }

    /**
     * {@inheritdoc}
     *
     * @api
     */
    public function getLogDir()
    {
        $parameters = $this->getLocalParams();
        if (isset($parameters['log_path'])) {
            return str_replace('%kernel.root_dir%', $this->getRootDir(), $parameters['log_path']);
        } else {
            return parent::getLogDir();
        }
    }

    /**
     * Get Mautic's local configuration file.
     *
     * @return array
     */
    public function getLocalParams()
    {
        static $localParameters;

        if (!is_array($localParameters)) {
            /** @var $paths */
            $root = $this->getRootDir();
            include $root.'/config/paths.php';

            if ($configFile = $this->getLocalConfigFile()) {
                /** @var $parameters */
                include $configFile;
                $localParameters = (isset($parameters) && is_array($parameters)) ? $parameters : [];
            } else {
                $localParameters = [];
            }

            //check for parameter overrides
            if (file_exists($root.'/config/parameters_local.php')) {
                /** @var $parameters */
                include $root.'/config/parameters_local.php';
                $localParameters = array_merge($localParameters, $parameters);
            }

            foreach ($localParameters as $k => &$v) {
                if (!empty($v) && is_string($v) && preg_match('/getenv\((.*?)\)/', $v, $match)) {
                    $v = (string) getenv($match[1]);
                }
            }
        }

        return $localParameters;
    }

    /**
     * Get local config file.
     *
     * @param bool $checkExists If true, then return false if the file doesn't exist
     *
     * @return bool
     */
    public function getLocalConfigFile($checkExists = true)
    {
        /** @var $paths */
        $root = $this->getRootDir();
        include $root.'/config/paths.php';

        if (isset($paths['local_config'])) {
            $paths['local_config'] = str_replace('%kernel.root_dir%', $root, $paths['local_config']);
            if (!$checkExists || file_exists($paths['local_config'])) {
                return $paths['local_config'];
            }
        }

        return false;
    }

    /**
     * Get the container file name or path.
     *
     * @param bool|true $fullPath
     *
     * @return string
     */
    public function getContainerFile($fullPath = true)
    {
        $fileName = $this->getContainerClass().'.php';

        if ($fullPath) {
            // Override the container class for the local instance
            $params        = $this->getLocalParams();
            $containerPath = (isset($params['container_path'])) ? $params['container_path'] : $this->getCacheDir();

            if (!file_exists($containerPath)) {
                @mkdir($containerPath, 0755, true);
            }

            $containerPath = (isset($params['container_path'])) ? $params['container_path'] : $this->getCacheDir();

            $fileName = $containerPath.'/'.$fileName;
        }

        return $fileName;
    }

    /**
     * Initializes the service container.
     *
     * The cached version of the service container is used when fresh, otherwise the
     * container is built.
     */
    protected function initializeContainer()
    {
        $class = $this->getContainerClass();

        $cache = new \Symfony\Component\Config\ConfigCache($this->getContainerFile(true), $this->debug);

        $fresh = file_exists($this->getCacheDir().'/classes.php');
        if (!$cache->isFresh()) {
            $container = $this->buildContainer();
            $container->compile();
            $this->dumpContainer($cache, $container, $class, $this->getContainerBaseClass());

            if ($this->debug) {
                $fresh = false;
            }
        }

        require_once $cache->getPath();

        $this->container = new $class();
        $this->container->set('kernel', $this);

        // Warm up the cache if classes.php is missing or in dev mode
        if (!$fresh && $this->container->has('cache_warmer')) {
            $this->container->get('cache_warmer')->warmUp($this->container->getParameter('kernel.cache_dir'));
        }
    }

    /**
     * Builds the service container.
     *
     * @return \Symfony\Component\DependencyInjection\ContainerBuilder The compiled service container
     *
     * @throws \RuntimeException
     */
    protected function buildContainer()
    {
        foreach (['cache' => $this->getCacheDir(), 'logs' => $this->getLogDir()] as $name => $dir) {
            if (!is_dir($dir)) {
                if (false === @mkdir($dir, 0777, true) && !is_dir($dir)) {
                    throw new \RuntimeException(sprintf("Unable to create the %s directory (%s)\n", $name, $dir));
                }
            } elseif (!is_writable($dir)) {
                throw new \RuntimeException(sprintf("Unable to write in the %s directory (%s)\n", $name, $dir));
            }
        }

        $container = $this->getContainerBuilder();
        $container->addObjectResource($this);
        $this->prepareContainer($container);

        if (null !== $cont = $this->registerContainerConfiguration($this->getContainerLoader($container))) {
            $container->merge($cont);
        }

        // Only rebuild the classes if it doesn't exist or if the kernel is booted through the console meaning likely cache:clear is used
        if (defined('IN_MAUTIC_CONSOLE') || !file_exists($this->getCacheDir().'/classes.php')) {
            $container->addCompilerPass(new DependencyInjection\AddClassesToCachePass($this));
        }

        // Environmentally set parameters
        $container->addResource(new EnvParametersResource('SYMFONY__'));
        $container->addResource(new EnvParametersResource('MAUTIC__'));

        return $container;
    }
}
