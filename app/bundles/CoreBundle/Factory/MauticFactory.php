<?php

namespace Mautic\CoreBundle\Factory;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Model\AbstractCommonModel;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
        private ManagerRegistry $doctrine,
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
     * Guess the IP address from current session.
     *
     * @return string
     */
    public function getIpAddressFromRequest()
    {
        return $this->container->get('mautic.helper.ip_lookup')->getIpAddressFromRequest();
    }
}
