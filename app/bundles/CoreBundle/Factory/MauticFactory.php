<?php

namespace Mautic\CoreBundle\Factory;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Exception\FileNotFoundException;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\ThemeHelper;
use Mautic\CoreBundle\Model\AbstractCommonModel;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Twig\Helper\AssetsHelper;
use Mautic\CoreBundle\Twig\Helper\SlotsHelper;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\EmailBundle\MonitoredEmail\Mailbox;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @deprecated 2.0 to be removed in 3.0
 */
class MauticFactory
{
    /**
     * @param ModelFactory<object> $modelFactory
     */
    public function __construct(
        private ContainerInterface $container,
        private ModelFactory $modelFactory,
        private CorePermissions $security,
        private AuthorizationCheckerInterface $authorizationChecker,
        private RequestStack $requestStack,
        private ManagerRegistry $doctrine,
        private Mailbox $mailbox,
        private ThemeHelper $themeHelper,
        private IntegrationHelper $integrationHelper,
        private SlotsHelper $slotsHelper,
        private AssetsHelper $assetsHelper,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Get a model instance from the service container.
     *
     * @return AbstractCommonModel<object>
     *
     * @throws \InvalidArgumentException
     */
    public function getModel($modelNameKey): \Mautic\CoreBundle\Model\MauticModelInterface
    {
        return $this->modelFactory->getModel($modelNameKey);
    }

    /**
     * Retrieves Mautic's security object.
     *
     * @return CorePermissions
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
     * Retrieves Doctrine EntityManager.
     *
     * @return EntityManager
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

    public function getDispatcher(): ?EventDispatcherInterface
    {
        return $this->container->get('event_dispatcher');
    }

    /**
     * Retrieves request.
     *
     * @return Request|null
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
     */
    public function getDate($string = null, $format = null, $tz = 'local'): DateTimeHelper
    {
        return new DateTimeHelper($string, $format, $tz);
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
     * @return LoggerInterface
     */
    public function getLogger($system = false)
    {
        if ($system) {
            return $this->logger;
        }

        return $this->container->get('monolog.logger.mautic');
    }

    /**
     * Get a mautic helper service.
     *
     * @return object
     */
    public function getHelper($helper)
    {
        switch ($helper) {
            case 'mailbox':
                return $this->mailbox;
            case 'theme':
                return $this->themeHelper;
            case 'integration':
                return $this->integrationHelper;
            case 'template.slots':
                return $this->slotsHelper;
            case 'template.assets':
                return $this->assetsHelper;
            default:
                @trigger_error('MauticFactory::getHelper with "'.$helper.'" is deprecated.', E_USER_DEPRECATED);

                return $this->container->get('mautic.helper.'.$helper);
        }
    }

    public function getKernel(): ?KernelInterface
    {
        return $this->container->get('kernel');
    }

    public function serviceExists($service): bool
    {
        return $this->container->has($service);
    }

    /**
     * @param string $service
     *
     * @return object|bool
     */
    public function get($service)
    {
        if ($this->serviceExists($service)) {
            return $this->container->get($service);
        }

        return false;
    }
}
