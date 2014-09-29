<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

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
     * Major version number
     *
     * @const integer
     */
    const EXTRA_VERSION = '-dev';

    /**
     * {@inheritdoc}
     */
    public function registerBundles()
    {
        $bundles = array(
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new Knp\Bundle\MenuBundle\KnpMenuBundle(),
            new FOS\OAuthServerBundle\FOSOAuthServerBundle(),
            new Bazinga\OAuthServerBundle\BazingaOAuthServerBundle(),
            new FOS\RestBundle\FOSRestBundle(),
            new JMS\SerializerBundle\JMSSerializerBundle(),
        );

        //dynamically register Mautic Bundles
        $searchPath = __DIR__.'/bundles';
        $finder     = new \Symfony\Component\Finder\Finder();
        $finder->files()
            ->in($searchPath)
            ->name('*Bundle.php');

        foreach ($finder as $file) {
            $path       = substr($file->getRealPath(), strlen($searchPath) + 1, -4);
            $parts      = explode(DIRECTORY_SEPARATOR, $path);
            $class      = array_pop($parts);
            $namespace  = "Mautic\\" . implode('\\', $parts);
            $class      = $namespace.'\\'.$class;
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

        if (in_array($this->getEnvironment(), array('dev', 'test'))) {
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
            $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
            $bundles[] = new Nelmio\ApiDocBundle\NelmioApiDocBundle();
            //$bundles[] = new Webfactory\Bundle\ExceptionsBundle\WebfactoryExceptionsBundle();
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
        $loader->load(__DIR__.'/config/config_'.$this->getEnvironment().'.php');
    }

    /**
     * Retrieves the application's version number
     *
     * @return string
     */
    public function getVersion()
    {
        $version = self::MAJOR_VERSION . '.' . self::MINOR_VERSION . '.' . self::PATCH_VERSION;

        if (self::EXTRA_VERSION != '') {
            $version .= self::EXTRA_VERSION;
        }

        return $version;
    }
}
