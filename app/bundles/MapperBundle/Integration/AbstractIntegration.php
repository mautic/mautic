<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\MapperBundle\Integration;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\MapperBundle\Entity\ApplicationIntegration;
use Mautic\MapperBundle\Entity\ApplicationIntegrationRepository;
use Mautic\MapperBundle\Entity\ApplicationObjectMapper;
use Mautic\MapperBundle\Entity\ApplicationObjectMapperRepository;

abstract class AbstractIntegration
{
    protected $factory;
    protected $repository;
    protected $objectsRepository;
    protected $entity;
    protected $data;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
        $this->repository = $this->factory->getEntityManager()->getRepository('MauticMapperBundle:ApplicationIntegration');
        $this->objectsRepository = $this->factory->getEntityManager()->getRepository('MauticMapperBundle:ApplicationObjectMapper');
    }

    /**
     * Determines what priority the network should have against the other networks
     *
     * @return mixed
     */
    public function getPriority()
    {
        return 9999;
    }

    /**
     * Return Application Link
     *
     * @return string
     */
    public function getSaveLink()
    {
        return $this->factory->getRouter()->generate('mautic_mapper_save',array('application' => $this->getAppAlias()));
    }

    /**
     * Return Application Link
     *
     * @return string
     */
    public function getAppLink()
    {
        return $this->factory->getRouter()->generate('mautic_mapper_integration',array('application' => $this->getAppAlias()));
    }

    /**
     * Return callback link
     *
     * @return string
     */
    public function getCallbackLink()
    {
        return $this->factory->getRouter()->generate('mautic_mapper_callback',array('application' => $this->getAppAlias()), true);
    }

    /**
     * Returns the name of the application
     *
     * @return string
     */
    abstract public function getAppAlias();

    /**
     * Returns the name of the application
     *
     * @return string
     */
    abstract public function getAppName();

    /**
     * Returns the image source from application
     *
     * @return string
     */
    abstract public function getImage();

    /**
     * Returns supported objects from this integration
     *
     * @return array
     */
    abstract public function getSupportedObjects();

    /**
     * Returns settings array for library
     *
     * @return string
     */
    abstract public function getSettings();

    /**
     * @return ApplicationIntegrationRepository
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * Return Application Integration Entity
     *
     * @return ApplicationIntegration
     */
    public function getEntity()
    {
        $this->entity = $this->repository->findOneBy(array('name' => $this->getAppName()));
        if (is_null($this->entity)) {
            $this->entity = new ApplicationIntegration();
        }
        $this->entity->setName($this->getAppName());

        return $this->entity;
    }

    /**
     * @return ApplicationObjectMapperRepository
     */
    public function getObjectsRepository()
    {
        return $this->factory->getEntityManager()->getRepository('MauticMapperBundle:ApplicationObjectMapper');
    }

    /**
     * @return array
     */
    public function getMappedObjects()
    {
        $objects = $this->getObjectsRepository()->findBy(
            array('applicationIntegrationId' => $this->getEntity()->getId()),
            array('objectName' => 'ASC')
        );
        if (empty($objects)) {
            foreach ($this->getSupportedObjects() as $objectName) {
                $object = new ApplicationObjectMapper();
                $object->setObjectName($objectName);
                $objects[] = $object;
            }
        }

        return $objects;
    }

    public function getMappedObject($object)
    {
        $objectEntity = $this->getObjectsRepository()->findOneBy(
            array(
                'applicationIntegrationId' => $this->getEntity()->getId(),
                'objectName' => $object
            )
        );
        if (empty($objectEntity)) {
            $objectEntity = new ApplicationObjectMapper();
            $objectEntity->setObjectName($object);
        }

        return $objectEntity;
    }
}