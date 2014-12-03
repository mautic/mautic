<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Mautic Application Kernel
 */
class AppKernel extends Kernel
{

    /**
     * Major version number
     *
     * @const integer
     */
    const MAJOR_VERSION = 1;

    /**
     * Minor version number
     *
     * @const integer
     */
    const MINOR_VERSION = 0;

    /**
     * Patch version number
     *
     * @const integer
     */
    const PATCH_VERSION = 0;

    /**
     * Extra version identifier
     *
     * This constant is used to define additional version segments such as development
     * or beta status.
     *
     * @const string
     */
    const EXTRA_VERSION = '-beta2-dev';

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        parent::boot();

        // It's only after we've booted that we have access to the container, so here is where we will check if addon bundles are enabled
        foreach ($this->getBundles() as $name => $bundle) {
            if ($bundle instanceof \Mautic\AddonBundle\Bundle\AddonBundleBase) {
                if (!$bundle->isEnabled()) {
                    unset($this->bundles[$name]);
                    unset($this->bundleMap[$name]);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        if (strpos($request->getRequestUri(), 'installer') !== false) {
            define('MAUTIC_INSTALLER', 1);
        } else {
            //set the table prefix before boot
            $localParams = $this->getLocalParams();
            $prefix      = isset($localParams['db_table_prefix']) ? $localParams['db_table_prefix'] : '';
            define('MAUTIC_TABLE_PREFIX', $prefix);
        }

        if (false === $this->booted) {
            $this->boot();
        }

        if (strpos($request->getRequestUri(), 'installer') === false && !$this->isInstalled()) {
            //the context is not populated at this point so have to do it manually
            $router = $this->getContainer()->get('router');
            $requestContext = new \Symfony\Component\Routing\RequestContext();
            $requestContext->fromRequest($request);
            $router->setContext($requestContext);

            //return new RedirectResponse();
            return new RedirectResponse($router->generate('mautic_installer_home'));
        }

        return parent::handle($request, $type, $catch);
    }

    /**
     * {@inheritdoc}
     */
    public function registerBundles()
    {
        $bundles = array(
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle(),
            new Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new Knp\Bundle\MenuBundle\KnpMenuBundle(),
            new FOS\OAuthServerBundle\FOSOAuthServerBundle(),
            new Bazinga\OAuthServerBundle\BazingaOAuthServerBundle(),
            new FOS\RestBundle\FOSRestBundle(),
            new JMS\SerializerBundle\JMSSerializerBundle(),
        );

        //dynamically register Mautic Bundles
        $searchPath = __DIR__ . '/bundles';
        $finder     = new \Symfony\Component\Finder\Finder();
        $finder->files()
            ->in($searchPath)
            ->depth('1')
            ->name('*Bundle.php');

        foreach ($finder as $file) {
            $path      = substr($file->getRealPath(), strlen($searchPath) + 1, -4);
            $parts     = explode(DIRECTORY_SEPARATOR, $path);
            $class     = array_pop($parts);
            $namespace = "Mautic\\" . implode('\\', $parts);
            $class     = $namespace . '\\' . $class;
            if (class_exists($class)) {
                $bundleInstance = new $class();
                if (method_exists($bundleInstance, 'isEnabled')) {
                    if ($bundleInstance->isEnabled()) {
                        $bundles[] = $bundleInstance;
                    }
                } else {
                    $bundles[] = $bundleInstance;
                }
            }
        }

        //dynamically register Mautic Addon Bundles
        $searchPath = dirname(__DIR__) . '/addons';
        $finder     = new \Symfony\Component\Finder\Finder();
        $finder->files()
            ->depth('1')
            ->in($searchPath)
            ->name('*Bundle.php');

        foreach ($finder as $file) {
            $path      = substr($file->getRealPath(), strlen($searchPath) + 1, -4);
            $parts     = explode(DIRECTORY_SEPARATOR, $path);
            $class     = array_pop($parts);
            $namespace = "MauticAddon\\" . implode('\\', $parts);
            $class     = $namespace . '\\' . $class;
            if (class_exists($class)) {
                $bundles[] = new $class();
            }
        }

        if (in_array($this->getEnvironment(), array('dev', 'test'))) {
            $bundles[] = new Symfony\Bundle\TwigBundle\TwigBundle();
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
            $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
            $bundles[] = new Nelmio\ApiDocBundle\NelmioApiDocBundle();
            $bundles[] = new Webfactory\Bundle\ExceptionsBundle\WebfactoryExceptionsBundle();
        }

        if (in_array($this->getEnvironment(), array('test'))) {
            $bundles[] = new Liip\FunctionalTestBundle\LiipFunctionalTestBundle();
        }

        return $bundles;
    }

    /**
     * {@inheritdoc}
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__ . '/config/config_' . $this->getEnvironment() . '.php');
    }

    /**
     * Retrieves the application's version number
     *
     * @return string
     */
    public function getVersion()
    {
        return self::MAJOR_VERSION . '.' . self::MINOR_VERSION . '.' . self::PATCH_VERSION . self::EXTRA_VERSION;
    }

    /**
     * Checks if the application has been installed
     *
     * @return bool
     */
    private function isInstalled()
    {
        // If the config file doesn't even exist, no point in checking further
        if (file_exists(__DIR__ . '/config/local.php')) {
            /** @var \Mautic\InstallBundle\Configurator\Configurator $configurator */
            $configurator = $this->getContainer()->get('mautic.configurator');
            $params       = $configurator->getParameters();

            // Check the DB Driver, Name, and User
            if ((isset($params['db_driver']) && $params['db_driver'])
                && (isset($params['db_user']) && $params['db_user'])
                && (isset($params['db_name']) && $params['db_name'])) {
                return true;
            }
        }

        return false;
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
            $envFolder = (strpos($parameters['cache_path'], -1) != '/') ? '/' . $this->environment : $this->environment;
            return str_replace('%kernel.root_dir%', $this->getRootDir(), $parameters['cache_path'] . $envFolder);
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
     * Get Mautic's local configuration file
     *
     * @return array
     */
    private function getLocalParams()
    {
        static $parameters;

        if (!is_array($parameters)) {
            $localConfig = $this->rootDir . '/config/local.php';
            if (file_exists($localConfig)) {
                include $localConfig;
            } else {
                $parameters = array();
            }
        }

        return $parameters;
    }
}
