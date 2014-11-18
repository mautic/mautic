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
        $clientEntity = $this->factory->getEntityManager('mapper.ApplicationClient');

        echo '<pre>';
        var_dump($clientEntity);
        die(__FILE__);

        return $this->factory->getEntityManager('mapper.ApplicationObjectMapper')->getOneBy(array(
            'object_name' => $this->getBaseName(),
            'application_client_id' => $clientEntity->getId()
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