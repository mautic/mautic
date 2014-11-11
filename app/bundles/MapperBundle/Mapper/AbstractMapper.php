<?php
namespace Mautic\MapperBundle\Mapper;

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
}