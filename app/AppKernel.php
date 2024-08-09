<?php

use Mautic\CoreBundle\Loader\ParameterLoader;
use Mautic\CoreBundle\Release\ThisRelease;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Mautic Application Kernel.
 */
class AppKernel extends Kernel
{
    /**
     * @var bool|null
     */
    private $installed;

    /**
     * @var ParameterLoader|null
     */
    private $parameterLoader;

    /**
     * @var string
     */
    private $projectDir;

    /**
     * @param string $environment The environment
     * @param bool   $debug       Whether to enable debugging or not
     *
     * @api
     */
    public function __construct($environment, $debug)
    {
        $metadata = ThisRelease::getMetadata();

        defined('MAUTIC_ENV') or define('MAUTIC_ENV', $environment);
        defined('MAUTIC_VERSION') or define('MAUTIC_VERSION', $metadata->getVersion());

        /**
         * This is required for Doctrine's automatic database detection. When Mautic hasn't been
         * installed yet, we don't have a database to connect to, causing automatic database platform
         * detection to fail. We use the MAUTIC_DB_SERVER_VERSION constant to temporarily set a server_version
         * if no database settings have been provided yet.
         */
        if (!defined('MAUTIC_DB_SERVER_VERSION')) {
            $localConfigFile = ParameterLoader::getLocalConfigFile($this->getApplicationDir().'/app', false);
            define('MAUTIC_DB_SERVER_VERSION', file_exists($localConfigFile) ? null : '5.7');
        }

        parent::__construct($environment, $debug);
    }

    public function handle(Request $request, $type = HttpKernelInterface::MAIN_REQUEST, $catch = true): Response
    {
        if (false !== strpos($request->getRequestUri(), 'installer') || !$this->isInstalled()) {
            defined('MAUTIC_INSTALLER') or define('MAUTIC_INSTALLER', 1);
        }

        if (defined('MAUTIC_INSTALLER')) {
            $uri = $request->getRequestUri();
            if (false === strpos($uri, 'installer')) {
                $base   = $request->getBaseUrl();
                $prefix = '';
                // check to see if the .htaccess file exists or if not running under apache
                if (false === stripos($request->server->get('SERVER_SOFTWARE', ''), 'apache')
                    || !file_exists($this->getProjectDir().'/.htaccess')
                    && false === strpos(
                        $base,
                        'index'
                    )
                ) {
                    $prefix .= '/index.php';
                }

                return new RedirectResponse($request->getUriForPath($prefix.'/installer'));
            }
        }

        if (false === $this->booted) {
            $this->boot();
        }

        /*
         * If we've already sent the response headers, and we have a session
         * set in the request, set that as the session in the container.
         */
        if (headers_sent() && $request->hasSession()) {
            $this->getContainer()->set('session', $request->getSession());
        }

        // Check for an an active db connection and die with error if unable to connect
        if (!defined('MAUTIC_INSTALLER')) {
            $db = $this->getContainer()->get('database_connection');
            try {
                $db->connect();
            } catch (\Exception $e) {
                error_log($e);
                throw new \Mautic\CoreBundle\Exception\DatabaseConnectionException($this->getContainer()->get('translator')->trans('mautic.core.db.connection.error', ['%code%' => $e->getCode()]), 0, $e);
            }
        }

        return parent::handle($request, $type, $catch);
    }

    public function registerBundles(): iterable
    {
        $bundles = [
            // Symfony/Core Bundles
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle(),
            new Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle(),
            new Knp\Bundle\MenuBundle\KnpMenuBundle(),
            new FOS\OAuthServerBundle\FOSOAuthServerBundle(),
            new FOS\RestBundle\FOSRestBundle(),
            new JMS\SerializerBundle\JMSSerializerBundle(),
            new Oneup\UploaderBundle\OneupUploaderBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new LightSaml\SymfonyBridgeBundle\LightSamlSymfonyBridgeBundle(),
            new LightSaml\SpBundle\LightSamlSpBundle(),
            new Noxlogic\RateLimitBundle\NoxlogicRateLimitBundle(),
            new FM\ElfinderBundle\FMElfinderBundle(),
            new Exercise\HTMLPurifierBundle\ExerciseHTMLPurifierBundle(),

            // Mautic Bundles
            new Mautic\ApiBundle\MauticApiBundle(),
            new Mautic\AssetBundle\MauticAssetBundle(),
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
            new Mautic\IntegrationsBundle\IntegrationsBundle(),
            new Mautic\LeadBundle\MauticLeadBundle(),
            new Mautic\MarketplaceBundle\MarketplaceBundle(),
            new Mautic\MessengerBundle\MauticMessengerBundle(),
            new Mautic\NotificationBundle\MauticNotificationBundle(),
            new Mautic\PageBundle\MauticPageBundle(),
            new Mautic\PluginBundle\MauticPluginBundle(),
            new Mautic\PointBundle\MauticPointBundle(),
            new Mautic\ReportBundle\MauticReportBundle(),
            new Mautic\SmsBundle\MauticSmsBundle(),
            new Mautic\StageBundle\MauticStageBundle(),
            new Mautic\StatsBundle\MauticStatsBundle(),
            new Mautic\UserBundle\MauticUserBundle(),
            new Mautic\WebhookBundle\MauticWebhookBundle(),
            new Mautic\CacheBundle\MauticCacheBundle(),
        ];

        // dynamically register Mautic Plugin Bundles
        $searchPath = $this->getApplicationDir().'/plugins';
        $finder     = new \Symfony\Component\Finder\Finder();
        $finder->files()
            ->followLinks()
            ->depth('1')
            ->in($searchPath)
            ->name('*Bundle.php');

        foreach ($finder as $file) {
            $dirname  = basename($file->getRelativePath());
            $filename = substr($file->getFilename(), 0, -4);

            $class = '\\MauticPlugin\\'.$dirname.'\\'.$filename;
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
            $bundles[] = new Fidry\PsyshBundle\PsyshBundle();
            $bundles[] = new Symfony\Bundle\MakerBundle\MakerBundle();
        }

        if (in_array($this->getEnvironment(), ['test'])) {
            $bundles[] = new Liip\FunctionalTestBundle\LiipFunctionalTestBundle();
            $bundles[] = new Liip\TestFixturesBundle\LiipTestFixturesBundle();
        }

        // Check for local bundle inclusion
        if (file_exists($this->getProjectDir().'/config/bundles_local.php')) {
            include $this->getProjectDir().'/config/bundles_local.php';
        }

        return $bundles;
    }

    protected function build(ContainerBuilder $container): void
    {
        $container->registerForAutoconfiguration(\Mautic\CoreBundle\Model\MauticModelInterface::class)
            ->addTag(\Mautic\CoreBundle\DependencyInjection\Compiler\ModelPass::TAG);
    }

    public function boot(): void
    {
        if (true === $this->booted) {
            return;
        }

        // load parameters with defaults into the environment
        $parameterLoader = $this->getParameterLoader();
        $parameterLoader->loadIntoEnvironment();
        if (!defined('MAUTIC_TABLE_PREFIX')) {
            // Set the table prefix before boot.
            // Firstly look into environment variables.
            $prefix = $_SERVER['MAUTIC_TABLE_PREFIX'];
            // Secondly look into the local.php file.
            if (empty($prefix)) {
                $prefix = $parameterLoader->getLocalParameterBag()->get('db_table_prefix', '');
            }

            define('MAUTIC_TABLE_PREFIX', $prefix);
        }

        // init bundles
        $this->initializeBundles();

        // init container
        $this->initializeContainer();

        // boot bundles
        foreach ($this->getBundles() as $name => $bundle) {
            $bundle->setContainer($this->container);
            $bundle->boot();
        }

        $this->booted = true;
    }

    protected function prepareContainer(ContainerBuilder $container): void
    {
        $container->setParameter('mautic.application_dir', $this->getApplicationDir());

        parent::prepareContainer($container);
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load($this->getApplicationDir().'/app/config/config_'.$this->getEnvironment().'.php');
    }

    /**
     * Retrieves the application's version number.
     */
    public function getVersion(): string
    {
        return MAUTIC_VERSION;
    }

    /**
     * Checks if the application has been installed.
     */
    protected function isInstalled(): bool
    {
        if (null === $this->installed) {
            $localParameters = $this->getParameterLoader()->getLocalParameterBag();
            $dbDriver        = $localParameters->get('db_driver');
            $siteUrl         = $localParameters->get('site_url');

            $this->installed = !empty($dbDriver) && !empty($siteUrl);
        }

        return $this->installed;
    }

    public function getApplicationDir(): string
    {
        return dirname(__DIR__);
    }

    public function getProjectDir()
    {
        if (null === $this->projectDir) {
            $r = new \ReflectionObject($this);

            if (!is_file($dir = $r->getFileName())) {
                throw new \LogicException(sprintf('Cannot auto-detect project dir for kernel of class "%s".', $r->name));
            }

            // We need 1 level deeper than the parent method, as the app folder contains a composer.json file
            $dir = $rootDir = \dirname($dir, 2);
            while (!is_file($dir.'/composer.json')) {
                if ($dir === \dirname($dir)) {
                    return $this->projectDir = $rootDir;
                }
                $dir = \dirname($dir);
            }
            $this->projectDir = $dir;
        }

        return $this->projectDir;
    }

    /**
     * @api
     */
    public function getCacheDir(): string
    {
        if ($cachePath = $this->getParameterLoader()->getLocalParameterBag()->get('cache_path')) {
            $envFolder = ('/' != substr($cachePath, -1)) ? '/'.$this->environment : $this->environment;

            return str_replace('%kernel.project_dir%', $this->getProjectDir(), $cachePath.$envFolder);
        }

        return $this->getProjectDir().'/var/cache/'.$this->getEnvironment();
    }

    public function getLogDir(): string
    {
        if ($logPath = $this->getParameterLoader()->getLocalParameterBag()->get('log_path')) {
            return str_replace('%kernel.project_dir%', $this->getProjectDir(), $logPath);
        }

        return $this->getProjectDir().'/var/logs';
    }

    /**
     * Get local config file.
     */
    public function getLocalConfigFile(): string
    {
        return ParameterLoader::getLocalConfigFile($this->getApplicationDir().'/app');
    }

    private function getParameterLoader(): ParameterLoader
    {
        if ($this->parameterLoader) {
            return $this->parameterLoader;
        }

        return $this->parameterLoader = new ParameterLoader();
    }
}
