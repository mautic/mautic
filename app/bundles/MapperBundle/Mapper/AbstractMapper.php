<?php
namespace Mautic\MapperBundle\Mapper;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Form\FormBuilderInterface;

abstract class AbstractMapper
{
    /**
     * @var
     */
    protected $factory;

    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
    }
    /**
     * Return base name of class
     *
     * @return string
     */
    public function getBaseName()
    {
        $parts = explode('\\',get_class($this));
        $name = substr(end($parts),0,-6);
        return $name;
    }

    /**
     * Return Entity
     *
     * @return mixed
     */
    public function getEntity()
    {
        $client = $this->factory->getRequest()->get('client');
        $model  = $this->factory->getModel('mapper.ApplicationClient');
        $clientEntity = $model->loadByAlias($client);

        if ($clientEntity == null) {
            return null;
        }

        return $this->factory->getEntityManager()->getRepository('MauticMapperBundle:ApplicationObjectMapper')->findOneBy(array(
            'objectName' => $this->getBaseName(),
            'applicationClientId' => $clientEntity->getId()
        ));
    }

    /**
     * Must be implement by MapperObject
     *
     * @param MauticFactory $factory
     * @param FormBuilderInterface $builder
     * @param array $options
     * @return mixed
     */
    abstract public function buildForm(MauticFactory $factory,FormBuilderInterface $builder, array $options);
}