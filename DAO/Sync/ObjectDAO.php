<?php

namespace MauticPlugin\MauticIntegrationsBundle\DAO\Sync;

/**
 * Class ObjectDAO
 * @package Mautic\PluginBundle\Model\Sync\DAO
 */
class ObjectDAO
{
    const CONTACT_ENTITY = 'contact';

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $entity;

    /**
     * @var mixed[] name => value
     */
    private $fields = [];

    /**
     * ObjectDAO constructor.
     * @param int       $id
     * @param string    $entity
     */
    public function __construct($id, $entity)
    {
        $this->id = $id;
        $this->entity = $entity;
    }

    /**
     * @param string    $name
     * @param mixed     $value
     * @return $this
     */
    public function addField($name, $value)
    {
        $this->fields[$name] = $value;

        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getField($name)
    {
        if (!isset($this->fields[$name])) {
            return null;
        }
        return $this->fields[$name];
    }

    /**
     * @return mixed[] name => value
     */
    public function getFields()
    {
        return $this->fields;
    }
}
