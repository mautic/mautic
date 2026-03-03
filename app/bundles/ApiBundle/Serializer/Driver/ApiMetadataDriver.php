<?php

namespace Mautic\ApiBundle\Serializer\Driver;

use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Metadata\PropertyMetadata;
use Metadata\ClassMetadata as BaseClassMetadata;
use Metadata\Driver\DriverInterface;

class ApiMetadataDriver implements DriverInterface
{
    private ?ClassMetadata $metadata = null;

    /**
     * @var PropertyMetadata[]
     */
    private array $properties = [];

    private string $groupPrefix = '';

    private string $defaultVersion = '1.0';

    private ?string $currentPropertyName = null;

    /**
     * @throws \ReflectionException
     */
    public function loadMetadataForClass(\ReflectionClass $class): ?BaseClassMetadata
    {
        if ($class->hasMethod('loadApiMetadata')) {
            $this->metadata = new ClassMetadata($class->getName());

            $class->getMethod('loadApiMetadata')->invoke(null, $this);

            $metadata = $this->metadata;

            $this->resetDefaults();

            return $metadata;
        } else {
            return new ClassMetadata($class->getName());
        }
    }

    private function resetDefaults(): void
    {
        $this->metadata       = null;
        $this->properties     = [];
        $this->defaultVersion = '1.0';
        $this->groupPrefix    = '';
    }

    /**
     * Set the root (base key).
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
     * @return $this
     */
    public function setDefaultVersion(string $version)
    {
        $this->defaultVersion = $version;

        return $this;
    }

    /**
     * Create a new property.
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

        $this->properties[$name]->serializedName = $serializedName ?? $name;

        if ($this->defaultVersion) {
            // Set the default version
            $this->setSinceVersion($this->defaultVersion);
        }

        $this->addGroup($this->groupPrefix.'Details');

        return $this;
    }

    /**
     * Create properties.
     *
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
                    [$prop, $serializedName] = $prop;
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
     * @return $this
     */
    public function addListProperties(array $properties)
    {
        $this->addProperties($properties, true);

        return $this;
    }

    /**
     * @return $this
     */
    public function setSinceVersion($version, $property = null)
    {
        if (null === $property) {
            $property = $this->getCurrentPropertyName();
        }

        $this->properties[$property]->sinceVersion = $version;

        return $this;
    }

    /**
     * @return $this
     */
    public function setUntilVersion($version, $property = null)
    {
        if (null === $property) {
            $property = $this->getCurrentPropertyName();
        }

        $this->properties[$property]->untilVersion = $version;

        return $this;
    }

    /**
     * @return $this
     */
    public function setSerializedName($name, $property = null)
    {
        if (null === $property) {
            $property = $this->getCurrentPropertyName();
        }

        $this->properties[$property]->serializedName = $name;

        return $this;
    }

    /**
     * Set the groups a property belongs to.
     *
     * @return $this
     */
    public function setGroups($groups, $property = null)
    {
        if (!is_array($groups)) {
            $groups = [$groups];
        }

        if (null === $property) {
            $property = $this->getCurrentPropertyName();
        }

        $this->properties[$property]->groups = $groups;

        return $this;
    }

    /**
     * Add a group the property belongs to.
     *
     * @param mixed $property
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
            if (null === $property) {
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
     * @return $this
     */
    public function setMaxDepth($depth, $property = null)
    {
        if (null === $property) {
            $property = $this->getCurrentPropertyName();
        }

        $this->properties[$property]->maxDepth = (int) $depth;

        return $this;
    }

    /**
     * Push the properties into ClassMetadata.
     */
    public function build(): void
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
