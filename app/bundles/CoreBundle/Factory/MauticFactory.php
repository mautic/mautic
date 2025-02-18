<?php

namespace Mautic\CoreBundle\Factory;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Model\AbstractCommonModel;

/**
 * @deprecated 2.0 to be removed in 3.0
 */
class MauticFactory
{
    /**
     * @param ModelFactory<object> $modelFactory
     */
    public function __construct(
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
}
