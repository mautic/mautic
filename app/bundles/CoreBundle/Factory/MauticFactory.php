<?php

namespace Mautic\CoreBundle\Factory;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Exception\FileNotFoundException;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Model\AbstractCommonModel;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @deprecated 2.0 to be removed in 3.0
 */
class MauticFactory
{
    /**
     * @var ContainerInterface
     */
    private $container;

    private $database;

    private $entityManager;
    private \Mautic\CoreBundle\Factory\ModelFactory $modelFactory;
    private \Mautic\CoreBundle\Security\Permissions\CorePermissions $corePermissions;
    private \Mautic\CoreBundle\Helper\UserHelper $userHelper;
    private \Symfony\Component\HttpFoundation\Session\Session $session;
    private \Doctrine\Bundle\DoctrineBundle\Registry $registry;
    private \Mautic\CoreBundle\Doctrine\Connection\ConnectionWrapper $connectionWrapper;
    private \Mautic\CoreBundle\Translation\Translator $translator;
    private \JMS\Serializer\Serializer $serializer;
    private \Mautic\CoreBundle\Helper\TemplatingHelper $templatingHelper;
    private \Symfony\Component\HttpKernel\Debug\TraceableEventDispatcher $traceableEventDispatcher;
    private \Symfony\Component\HttpFoundation\RequestStack $requestStack;
    private \Symfony\Component\Validator\Validator\TraceableValidator $traceableValidator;
    private \Mautic\CoreBundle\Helper\CoreParametersHelper $coreParametersHelper;
    private \Symfony\Bundle\FrameworkBundle\Routing\Router $router;
    private \Mautic\CoreBundle\Helper\PathsHelper $pathsHelper;
    private \Mautic\CoreBundle\Helper\ThemeHelper $themeHelper;
    private \Mautic\EmailBundle\Helper\MailHelper $mailHelper;
    private \Mautic\CoreBundle\Helper\IpLookupHelper $ipLookupHelper;
    private \Psr\Log\LoggerInterface $logger;
    private \Mautic\CoreBundle\Templating\Helper\AssetsHelper $assetsHelper;
    private \Mautic\CoreBundle\Templating\Helper\SlotsHelper $slotsHelper;
    private \Mautic\CoreBundle\Templating\Helper\FormHelper $formHelper;
    private \Mautic\CoreBundle\Templating\Helper\TranslatorHelper $translatorHelper;
    private \Symfony\Bundle\FrameworkBundle\Templating\Helper\RouterHelper $routerHelper;
    private \Mautic\CoreBundle\Helper\BundleHelper $bundleHelper;

    public function __construct(ContainerInterface $container, \Mautic\CoreBundle\Factory\ModelFactory $modelFactory, \Mautic\CoreBundle\Security\Permissions\CorePermissions $corePermissions, \Mautic\CoreBundle\Helper\UserHelper $userHelper, \Symfony\Component\HttpFoundation\Session\Session $session, \Doctrine\Bundle\DoctrineBundle\Registry $registry, \Mautic\CoreBundle\Doctrine\Connection\ConnectionWrapper $connectionWrapper, \Mautic\CoreBundle\Translation\Translator $translator, \JMS\Serializer\Serializer $serializer, \Mautic\CoreBundle\Helper\TemplatingHelper $templatingHelper, \Symfony\Component\HttpKernel\Debug\TraceableEventDispatcher $traceableEventDispatcher, \Symfony\Component\HttpFoundation\RequestStack $requestStack, \Symfony\Component\Validator\Validator\TraceableValidator $traceableValidator, \Mautic\CoreBundle\Helper\CoreParametersHelper $coreParametersHelper, \Symfony\Bundle\FrameworkBundle\Routing\Router $router, \Mautic\CoreBundle\Helper\PathsHelper $pathsHelper, \Mautic\CoreBundle\Helper\ThemeHelper $themeHelper, \Mautic\EmailBundle\Helper\MailHelper $mailHelper, \Mautic\CoreBundle\Helper\IpLookupHelper $ipLookupHelper, \Psr\Log\LoggerInterface $logger, \Mautic\CoreBundle\Templating\Helper\AssetsHelper $assetsHelper, \Mautic\CoreBundle\Templating\Helper\SlotsHelper $slotsHelper, \Mautic\CoreBundle\Templating\Helper\FormHelper $formHelper, \Mautic\CoreBundle\Templating\Helper\TranslatorHelper $translatorHelper, \Symfony\Bundle\FrameworkBundle\Templating\Helper\RouterHelper $routerHelper, \Mautic\CoreBundle\Helper\BundleHelper $bundleHelper)
    {
        $this->container = $container;
        $this->modelFactory = $modelFactory;
        $this->corePermissions = $corePermissions;
        $this->userHelper = $userHelper;
        $this->session = $session;
        $this->registry = $registry;
        $this->connectionWrapper = $connectionWrapper;
        $this->translator = $translator;
        $this->serializer = $serializer;
        $this->templatingHelper = $templatingHelper;
        $this->traceableEventDispatcher = $traceableEventDispatcher;
        $this->requestStack = $requestStack;
        $this->traceableValidator = $traceableValidator;
        $this->coreParametersHelper = $coreParametersHelper;
        $this->router = $router;
        $this->pathsHelper = $pathsHelper;
        $this->themeHelper = $themeHelper;
        $this->mailHelper = $mailHelper;
        $this->ipLookupHelper = $ipLookupHelper;
        $this->logger = $logger;
        $this->assetsHelper = $assetsHelper;
        $this->slotsHelper = $slotsHelper;
        $this->formHelper = $formHelper;
        $this->translatorHelper = $translatorHelper;
        $this->routerHelper = $routerHelper;
        $this->bundleHelper = $bundleHelper;
    }

    /**
     * Get a model instance from the service container.
     *
     * @param $modelNameKey
     *
     * @return AbstractCommonModel
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
        return $this->corePermissions;
    }

    /**
     * Retrieves Symfony's security context.
     *
     * @return \Symfony\Component\Security\Core\SecurityContext
     */
    public function getSecurityContext()
    {
        return $this->container->get('security.context');
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
     * Retrieves session object.
     *
     * @return \Symfony\Component\HttpFoundation\Session\Session
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * Retrieves Doctrine EntityManager.
     *
     * @return \Doctrine\ORM\EntityManager
     */
    public function getEntityManager()
    {
        return ($this->entityManager) ? $this->entityManager : $this->registry->getManager();
    }

    public function setEntityManager(EntityManager $em)
    {
        $this->entityManager = $em;
    }

    /**
     * Retrieves Doctrine database connection for DBAL use.
     *
     * @return \Doctrine\DBAL\Connection
     */
    public function getDatabase()
    {
        return ($this->database) ? $this->database : $this->connectionWrapper;
    }

    /**
     * @param $db
     */
    public function setDatabase($db)
    {
        $this->database = $db;
    }

    /**
     * Retrieves Translator.
     *
     * @return \Mautic\CoreBundle\Translation\Translator
     */
    public function getTranslator()
    {
        if (defined('IN_MAUTIC_CONSOLE')) {
            /** @var \Mautic\CoreBundle\Translation\Translator $translator */
            $translator = $this->translator;

            $translator->setLocale(
                $this->getParameter('locale')
            );

            return $translator;
        }

        return $this->translator;
    }

    /**
     * Retrieves serializer.
     *
     * @return \JMS\Serializer\Serializer
     */
    public function getSerializer()
    {
        return $this->serializer;
    }

    /**
     * Retrieves templating service.
     *
     * @return \Symfony\Bundle\FrameworkBundle\Templating\DelegatingEngine
     */
    public function getTemplating()
    {
        return $this->templatingHelper->getTemplating();
    }

    /**
     * Retrieves event dispatcher.
     *
     * @return \Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher
     */
    public function getDispatcher()
    {
        return $this->traceableEventDispatcher;
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
            //likely in a test as the request is not populated for outside the container
            $request      = Request::createFromGlobals();
            $requestStack = new RequestStack();
            $requestStack->push($request);
        }

        return $request;
    }

    /**
     * Retrieves Symfony's validator.
     *
     * @return \Symfony\Component\Validator\Validator
     */
    public function getValidator()
    {
        return $this->traceableValidator;
    }

    /**
     * Retrieves Mautic system parameters.
     *
     * @return array
     */
    public function getSystemParameters()
    {
        return $this->container->getParameter('mautic.parameters');
    }

    /**
     * Retrieves a Mautic parameter.
     *
     * @param       $id
     * @param mixed $default
     *
     * @return bool|mixed
     */
    public function getParameter($id, $default = false)
    {
        return $this->coreParametersHelper->get($id, $default);
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
        return $this->router;
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
        return $this->pathsHelper->getSystemPath($name, $fullPath);
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
        return $this->themeHelper->getTheme($theme, $throwException);
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
        return $this->themeHelper->getInstalledThemes($specificFeature, $extended);
    }

    /**
     * Returns MailHelper wrapper for Swift_Message via $helper->message.
     *
     * @param bool $cleanSlate False to preserve current settings, i.e. to process batched emails
     *
     * @return MailHelper
     */
    public function getMailer($cleanSlate = true)
    {
        return $this->mailHelper->getMailer($cleanSlate);
    }

    /**
     * Guess the IP address from current session.
     *
     * @return string
     */
    public function getIpAddressFromRequest()
    {
        return $this->ipLookupHelper->getIpAddressFromRequest();
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
        return $this->ipLookupHelper->getIpAddress($ip);
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
            return $this->logger;
        } else {
            return $this->logger;
        }
    }

    /**
     * Get a mautic helper service.
     *
     * @param $helper
     *
     * @return object
     */
    public function getHelper($helper)
    {
        switch ($helper) {
            case 'template.assets':
                return $this->assetsHelper;
            case 'template.slots':
                return $this->slotsHelper;
            case 'template.form':
                return $this->formHelper;
            case 'template.translator':
                return $this->translatorHelper;
            case 'template.router':
                return $this->routerHelper;
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
        return $this->bundleHelper->getMauticBundles($includePlugins);
    }

    /**
     * Get's an array of details for enabled Mautic plugins.
     *
     * @return array
     */
    public function getPluginBundles()
    {
        return $this->bundleHelper->getPluginBundles();
    }

    /**
     * Gets an array of a specific bundle's config settings.
     *
     * @param        $bundleName
     * @param string $configKey
     * @param bool   $includePlugins
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function getBundleConfig($bundleName, $configKey = '', $includePlugins = false)
    {
        return $this->bundleHelper->getBundleConfig($bundleName, $configKey, $includePlugins);
    }

    /**
     * @param $service
     *
     * @return bool
     */
    public function serviceExists($service)
    {
        return $this->container->has($service);
    }

    /**
     * @param $service
     *
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
