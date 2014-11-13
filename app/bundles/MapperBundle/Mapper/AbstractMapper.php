<?php
namespace Mautic\MapperBundle\Mapper;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Form\FormBuilderInterface;

abstract class AbstractMapper
{
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
     * Must be implement by MapperObject
     *
     * @param MauticFactory $factory
     * @param FormBuilderInterface $builder
     * @param array $options
     * @return mixed
     */
    abstract public function buildForm(MauticFactory $factory,FormBuilderInterface $builder, array $options);
}