<?php

namespace Mautic\CoreBundle\Factory;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Exception\FileNotFoundException;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Model\AbstractCommonModel;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @deprecated 2.0 to be removed in 3.0
 */
class MauticFactory
{
    private ContainerInterface $container;

    /**
     * @var ModelFactory<object>
     */
    private ModelFactory $modelFactory;

    private CorePermissions $security;

    private AuthorizationCheckerInterface $authorizationChecker;

    private UserHelper $userHelper;

    private RequestStack $requestStack;

    private ManagerRegistry $doctrine;

    private Translator $translator;

    /**
     * @param ModelFactory<object> $modelFactory
     */
    public function __construct(
        ContainerInterface $container,
        ModelFactory $modelFactory,
        CorePermissions $security,
        AuthorizationCheckerInterface $authorizationChecker,
        UserHelper $userHelper,
        RequestStack $requestStack,
        ManagerRegistry $doctrine,
        Translator $translator
    ) {
        $this->container            = $container;
        $this->modelFactory         = $modelFactory;
        $this->security             = $security;
        $this->authorizationChecker = $authorizationChecker;
        $this->userHelper           = $userHelper;
        $this->requestStack         = $requestStack;
        $this->doctrine             = $doctrine;
        $this->translator           = $translator;
    }

    /**
     * Get a model instance from the service container.
     *
     * @return AbstractCommonModel<object>
     *
     * @throws \InvalidArgumentException
     */
    public function getModel($modelNameKey)
    {
        return $this->modelFactory->getModel($modelNameKey);
    }

    /**
     * Retrieves Mautic's security object.
     *
     * @return \Mautic\CoreBundle\Security\Permissions\CorePermissions
     */
    public function getSecurity()
    {
        return $this->security;
    }

    /**
     * Retrieves Symfony's security context.
     */
    public function getSecurityContext(): AuthorizationCheckerInterface
    {
        return $this->authorizationChecker;
    }

    /**
     * Retrieves user currently logged in.
     *
     * @param bool $nullIfGuest
     *
     * @return User|null
     */
    public function getUser($nullIfGuest = false)
    {
        return $this->userHelper->getUser($nullIfGuest);
    }

    /**
     * Retrieves Doctrine EntityManager.
     *
     * @return \Doctrine\ORM\EntityManager
     */
    public function getEntityManager()
    {
        $manager = $this->doctrine->getManager();
        \assert($manager instanceof EntityManager);

        return $manager;
    }

    /**
     * Retrieves Doctrine database connection for DBAL use.
     *
     * @return \Doctrine\DBAL\Connection
     */
    public function getDatabase()
    {
        return $this->doctrine->getConnection();
    }

    /**
     * Retrieves Translator.
     *
     * @return \Mautic\CoreBundle\Translation\Translator
     */
    public function getTranslator()
    {
        if (defined('IN_MAUTIC_CONSOLE')) {
            $translator = $this->translator;

            $translator->setLocale(
                $this->getParameter('locale')
            );

            return $translator;
        }

        return $this->translator;
    }

    /**
     * Retrieves twig service.
     *
     * @return \Twig\Environment
     */
    public function getTwig()
    {
        return $this->container->get('twig');
    }

    /**
     * Retrieves event dispatcher.
     *
     * @return \Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher
     */
    public function getDispatcher()
    {
        return $this->container->get('event_dispatcher');
    }

    /**
     * Retrieves request.
     *
     * @return \Symfony\Component\HttpFoundation\Request|null
     */
    public function getRequest()
    {
        $request = $this->requestStack->getCurrentRequest();
        if (empty($request)) {
            // likely in a test as the request is not populated for outside the container
            $request      = Request::createFromGlobals();
            $requestStack = new RequestStack();
            $requestStack->push($request);
        }

        return $request;
    }

    /**
     * Retrieves a Mautic parameter.
     *
     * @param mixed $default
     *
     * @return bool|mixed
     */
    public function getParameter($id, $default = false)
    {
        return $this->container->get('mautic.helper.core_parameters')->get($id, $default);
    }

    /**
     * Get DateTimeHelper.
     *
     * @param string $string
     * @param string $format
     * @param string $tz
     *
     * @return DateTimeHelper
     */
    public function getDate($string = null, $format = null, $tz = 'local')
    {
        return new DateTimeHelper($string, $format, $tz);
    }

    /**
     * Get Router.
     *
     * @return Router
     */
    public function getRouter()
    {
        return $this->container->get('router');
    }

    /**
     * Get the path to specified area.  Returns relative by default with the exception of cache and log
     * which will be absolute regardless of $fullPath setting.
     *
     * @param string $name
     * @param bool   $fullPath
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function getSystemPath($name, $fullPath = false)
    {
        return $this->container->get('mautic.helper.paths')->getSystemPath($name, $fullPath);
    }

    /**
     * Returns local config file path.
     *
     * @param bool $checkExists If true, returns false if file doesn't exist
     *
     * @return bool
     */
    public function getLocalConfigFile($checkExists = true)
    {
        /** @var \AppKernel $kernel */
        $kernel = $this->container->get('kernel');

        return $kernel->getLocalConfigFile($checkExists);
    }

    /**
     * Get the current environment.
     *
     * @return string
     */
    public function getEnvironment()
    {
        return $this->container->getParameter('kernel.environment');
    }

    /**
     * Returns if Symfony is in debug mode.
     *
     * @return mixed
     */
    public function getDebugMode()
    {
        return $this->container->getParameter('kernel.debug');
    }

    /**
     * returns a ThemeHelper instance for the given theme.
     *
     * @param string $theme
     * @param bool   $throwException
     *
     * @return mixed
     *
     * @throws FileNotFoundException
     * @throws \Exception
     */
    public function getTheme($theme = 'current', $throwException = false)
    {
        return $this->container->get('mautic.helper.theme')->getTheme($theme, $throwException);
    }

    /**
     * Gets a list of installed themes.
     *
     * @param string $specificFeature limits list to those that support a specific feature
     * @param bool   $extended        returns extended information about the themes
     *
     * @return array
     */
    public function getInstalledThemes($specificFeature = 'all', $extended = false)
    {
        return $this->container->get('mautic.helper.theme')->getInstalledThemes($specificFeature, $extended);
    }

    /**
     * Returns MailHelper wrapper for Email via $helper->message.
     *
     * @param bool $cleanSlate False to preserve current settings, i.e. to process batched emails
     *
     * @return MailHelper
     */
    public function getMailer($cleanSlate = true)
    {
        return $this->container->get('mautic.helper.mailer')->getMailer($cleanSlate);
    }

    /**
     * Guess the IP address from current session.
     *
     * @return string
     */
    public function getIpAddressFromRequest()
    {
        return $this->container->get('mautic.helper.ip_lookup')->getIpAddressFromRequest();
    }

    /**
     * Get an IpAddress entity for current session or for passed in IP address.
     *
     * @param string $ip
     *
     * @return IpAddress
     */
    public function getIpAddress($ip = null)
    {
        return $this->container->get('mautic.helper.ip_lookup')->getIpAddress($ip);
    }

    /**
     * Retrieves the application's version number.
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->container->get('kernel')->getVersion();
    }

    /**
     * Get Symfony's logger.
     *
     * @param bool|false $system
     *
     * @return \Monolog\Logger
     */
    public function getLogger($system = false)
    {
        if ($system) {
            return $this->container->get('logger');
        } else {
            return $this->container->get('monolog.logger.mautic');
        }
    }

    /**
     * Get a mautic helper service.
     *
     * @return object
     */
    public function getHelper($helper)
    {
        switch ($helper) {
            case 'template.assets':
                return $this->container->get('twig.helper.assets');
            case 'template.slots':
                return $this->container->get('twig.helper.slots');
            case 'template.form':
                return $this->container->get('twig.helper.form');
            case 'template.translator':
                return $this->container->get('twig.helper.translator');
            case 'template.router':
                return $this->container->get('twig.helper.router');
            default:
                return $this->container->get('mautic.helper.'.$helper);
        }
    }

    /**
     * Get's the Symfony kernel.
     *
     * @return \AppKernel
     */
    public function getKernel()
    {
        return $this->container->get('kernel');
    }

    /**
     * Get's an array of details for Mautic core bundles.
     *
     * @param bool|false $includePlugins
     *
     * @return array|mixed
     */
    public function getMauticBundles($includePlugins = false)
    {
        return $this->container->get('mautic.helper.bundle')->getMauticBundles($includePlugins);
    }

    /**
     * Get's an array of details for enabled Mautic plugins.
     *
     * @return array
     */
    public function getPluginBundles()
    {
        return $this->container->get('mautic.helper.bundle')->getPluginBundles();
    }

    /**
     * Gets an array of a specific bundle's config settings.
     *
     * @param string $configKey
     * @param bool   $includePlugins
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function getBundleConfig($bundleName, $configKey = '', $includePlugins = false)
    {
        return $this->container->get('mautic.helper.bundle')->getBundleConfig($bundleName, $configKey, $includePlugins);
    }

    /**
     * @return bool
     */
    public function serviceExists($service)
    {
        return $this->container->has($service);
    }

    /**
     * @return bool
     */
    public function get($service)
    {
        if ($this->serviceExists($service)) {
            return $this->container->get($service);
        }

        return false;
    }
}
