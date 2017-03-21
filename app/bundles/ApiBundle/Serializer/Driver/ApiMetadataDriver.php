<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Serializer\Driver;

use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Metadata\Driver\PhpDriver;
use JMS\Serializer\Metadata\PropertyMetadata;

class ApiMetadataDriver extends PhpDriver
{
    /**
     * @var ClassMetadata
     */
    private $metadata = null;

    /**
     * @var PropertyMetadata[]
     */
    private $properties = [];

    /**
     * @var string
     */
    private $groupPrefix = '';

    /**
     * @var null
     */
    private $defaultVersion = '1.0';

    /**
     * @var null
     */
    private $currentPropertyName = null;

    /**
     * @param \ReflectionClass $class
     * @param string           $file
     *
     * @return ClassMetadata
     */
    protected function loadMetadataFromFile(\ReflectionClass $class, $file)
    {
        if ($class->hasMethod('loadApiMetadata')) {
            $this->metadata = new ClassMetadata($class->getName());

            $this->properties     = [];
            $this->defaultVersion = '1.0';
            $this->groupPrefix    = '';

            $serializer = $class->getMethod('loadApiMetadata');
            $serializer->invoke(null, $this);

            $metadata       = $this->metadata;
            $this->metadata = null;

            return $metadata;
        }
    }

    /**
     * Set the root (base key).
     *
     * @param $root
     *
     * @return $this
     */
    public function setRoot($root)
    {
        $this->metadata->xmlRootName = $root;

        return $this;
    }

    /**
     * Set prefix for the List and Details groups.
     *
     * @param $name
     *
     * @return $this
     */
    public function setGroupPrefix($name)
    {
        $this->groupPrefix = $name;

        return $this;
    }

    /**
     * Set the default version for the properties if different than 1.0.
     *
     * @param $version
     *
     * @return $this
     */
    public function setDefaultVersion($version)
    {
        $this->defaultVersion = $version;

        return $this;
    }

    /**
     * Create a new property.
     *
     * @param $name
     *
     * @return $this
     */
    public function createProperty($name)
    {
        if (!isset($this->properties[$name])) {
            $this->properties[$name] = new PropertyMetadata($this->metadata->name, $name);
        }

        $this->currentPropertyName = $name;

        return $this;
    }

    /**
     * Add property and set default version and Details group.
     *
     * @param      $name
     * @param null $serializedName
     * @param bool $useGetter
     *
     * @return $this
     */
    public function addProperty($name, $serializedName = null, $useGetter = false)
    {
        if (empty($name)) {
            return $this;
        }

        $this->createProperty($name);

        if ($useGetter && !$this->properties[$name]->getter) {
            $this->properties[$name]->getter = 'get'.ucfirst($name);
        }

        if ($serializedName) {
            $this->properties[$name]->serializedName = $serializedName;
        }

        if ($this->defaultVersion !== null) {
            // Set the default version
            $this->setSinceVersion($this->defaultVersion);
        }

        if ($this->groupPrefix !== null) {
            // Auto add to the Details group
            $this->addGroup($this->groupPrefix.'Details');
        }

        return $this;
    }

    /**
     * Create properties.
     *
     * @param array      $properties
     * @param bool|false $addToListGroup
     * @param bool|false $useGetter
     *
     * @return $this
     */
    public function addProperties(array $properties, $addToListGroup = false, $useGetter = false)
    {
        foreach ($properties as $prop) {
            if (!empty($prop)) {
                $serializedName = null;
                if (is_array($prop)) {
                    list($prop, $serializedName) = $prop;
                }
                $this->addProperty($prop, $serializedName, $useGetter);

                if ($addToListGroup) {
                    $this->inListGroup();
                }
            }
        }

        return $this;
    }

    /**
     * Create properties and add to the List group.
     *
     * @param array $properties
     *
     * @return $this
     */
    public function addListProperties(array $properties)
    {
        $this->addProperties($properties, true);

        return $this;
    }

    /**
     * @param      $version
     * @param null $property
     *
     * @return $this
     */
    public function setSinceVersion($version, $property = null)
    {
        if ($property === null) {
            $property = $this->getCurrentPropertyName();
        }

        $this->properties[$property]->sinceVersion = $version;

        return $this;
    }

    /**
     * @param      $version
     * @param null $property
     *
     * @return $this
     */
    public function setUntilVersion($version, $property = null)
    {
        if ($property === null) {
            $property = $this->getCurrentPropertyName();
        }

        $this->properties[$property]->untilVersion = $version;

        return $this;
    }

    /**
     * @param      $name
     * @param null $property
     *
     * @return $this
     */
    public function setSerializedName($name, $property = null)
    {
        if ($property === null) {
            $property = $this->getCurrentPropertyName();
        }

        $this->properties[$property]->serializedName = $name;

        return $this;
    }

    /**
     * Set the groups a property belongs to.
     *
     * @param   $groups
     * @param   $property
     *
     * @return $this
     */
    public function setGroups($groups, $property = null)
    {
        if (!is_array($groups)) {
            $groups = [$groups];
        }

        if ($property === null) {
            $property = $this->getCurrentPropertyName();
        }

        $this->properties[$property]->groups = $groups;

        return $this;
    }

    /**
     * Add a group the property belongs to.
     *
     * @param      $group
     * @param null $property True to apply to all current properties
     *
     * @return $this
     */
    public function addGroup($group, $property = null)
    {
        if (true === $property) {
            foreach ($this->properties as $prop => $metadata) {
                $this->addGroup($group, $prop);
            }
        } else {
            if ($property === null) {
                $property = $this->getCurrentPropertyName();
            }

            $this->properties[$property]->groups[] = $group;
        }

        return $this;
    }

    /**
     * Add property to the List group.
     *
     * @return $this
     */
    public function inListGroup()
    {
        $this->properties[$this->currentPropertyName]->groups[] =
            $this->groupPrefix.'List';

        return $this;
    }

    /**
     * Set max depth for the property if an association.
     *
     * @param      $depth
     * @param null $property
     *
     * @return $this
     */
    public function setMaxDepth($depth, $property = null)
    {
        if ($property === null) {
            $property = $this->getCurrentPropertyName();
        }

        $this->properties[$property]->maxDepth = (int) $depth;

        return $this;
    }

    /**
     * Push the properties into ClassMetadata.
     */
    public function build()
    {
        foreach ($this->properties as $prop) {
            $this->metadata->addPropertyMetadata($prop);
        }

        $this->currentPropertyName = null;
        $this->properties          = [];
    }

    /**
     * @return string
     *
     * @throws \Exception
     */
    protected function getCurrentPropertyName()
    {
        if (empty($this->currentPropertyName)) {
            throw new \Exception('Current property is not set');
        }

        return $this->currentPropertyName;
    }
}
