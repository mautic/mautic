<?php

namespace Mautic\CoreBundle\Factory;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Model\AbstractCommonModel;
use Mautic\EmailBundle\Helper\MailHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

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
        private RequestStack $requestStack,
        private ManagerRegistry $doctrine,
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
