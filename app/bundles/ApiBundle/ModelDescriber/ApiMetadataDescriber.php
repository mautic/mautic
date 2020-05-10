<?php

/**
 * Please note that most of the code in this file is copied from:
 * vendor/nelmio/api-doc-bundle/ModelDescriber/JMSModelDescriber.php.
 *
 * We extend JMSModelDescriber in an attempt to reduce code duplication,
 * but there might be a better way to do this.
 */

namespace Mautic\ApiBundle\ModelDescriber;

use Doctrine\Common\Annotations\Reader;
use EXSyst\Component\Swagger\Schema;
use JMS\Serializer\Context;
use JMS\Serializer\Exclusion\GroupsExclusionStrategy;
use JMS\Serializer\Naming\PropertyNamingStrategyInterface;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Metadata\MetadataFactoryInterface;
use Nelmio\ApiDocBundle\Describer\ModelRegistryAwareTrait;
use Nelmio\ApiDocBundle\Model\Model;
use Nelmio\ApiDocBundle\ModelDescriber\Annotations\AnnotationsReader;
use Nelmio\ApiDocBundle\ModelDescriber\JMSModelDescriber;
use ReflectionClass;

class ApiMetadataDescriber extends JMSModelDescriber
{
    use ModelRegistryAwareTrait;

    private $factory;

    private $namingStrategy;

    private $doctrineReader;

    private $contexts = [];

    private $metadataStacks = [];

    /** @var \Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver */
    protected $driver;

    public function __construct(
        MetadataFactoryInterface $factory,
        PropertyNamingStrategyInterface $namingStrategy = null,
        Reader $reader,
        ApiMetadataDriver $driver
    ) {
        $this->factory        = $factory;
        $this->namingStrategy = $namingStrategy;
        $this->doctrineReader = $reader;
        $this->driver         = $driver;
    }

    public function supports(Model $model): bool
    {
        // This code is similar to Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver (also calls loadApiMetadata)
        $className = $model->getType()->getClassName();
        $class     = new ReflectionClass($className);

        if ($class->hasMethod('loadApiMetadata')) {
            return true;
        }

        return false;
    }

    public function describe(Model $model, Schema $schema)
    {
        $className = $model->getType()->getClassName();
        $class     = new ReflectionClass($className);

        // Call our ApiMetadataDriver function to have the same serialization behavior as for the API response
        $metadata = $this->driver->loadMetadataForClass($class);

        /**
         * All the code below was mostly copied from JMSModelDescriber.
         */
        if (null === $metadata) {
            throw new \InvalidArgumentException(sprintf('No metadata found for class %s.', $className));
        }

        $schema->setType('object');
        $annotationsReader = new AnnotationsReader($this->doctrineReader, $this->modelRegistry);
        $annotationsReader->updateDefinition(new \ReflectionClass($className), $schema);

        $isJmsV1    = null !== $this->namingStrategy;
        $properties = $schema->getProperties();

        $context = $this->getSerializationContext($model);
        $context->pushClassMetadata($metadata);
        foreach ($metadata->propertyMetadata as $item) {
            // filter groups
            if (null !== $context->getExclusionStrategy()
                && $context->getExclusionStrategy()->shouldSkipProperty($item, $context)
            ) {
                continue;
            }

            $context->pushPropertyMetadata($item);

            $name = true === $isJmsV1 ? $this->namingStrategy->translateName($item) : $item->serializedName;
            // read property options from Swagger Property annotation if it exists
            try {
                if (true === $isJmsV1 && property_exists($item, 'reflection') && null !== $item->reflection) {
                    $reflection = $item->reflection;
                } else {
                    $reflection = new \ReflectionProperty($item->class, $item->name);
                }

                $property = $properties->get($annotationsReader->getPropertyName($reflection, $name));
                $groups   = $this->computeGroups($context, $item->type);
                $annotationsReader->updateProperty($reflection, $property, $groups);
            } catch (\ReflectionException $e) {
                $property = $properties->get($name);
            }

            if (null !== $property->getType() || null !== $property->getRef()) {
                $context->popPropertyMetadata();

                continue;
            }
            if (null === $item->type) {
                $properties->remove($name);
                $context->popPropertyMetadata();

                continue;
            }

            $this->describeItem($item->type, $property, $context, $item);
            $context->popPropertyMetadata();
        }
        $context->popClassMetadata();
    }

    private function computeGroups(Context $context, array $type = null)
    {
        if (null === $type || true !== $this->propertyTypeUsesGroups($type)) {
            return null;
        }

        $groupsExclusion = $context->getExclusionStrategy();
        if (!($groupsExclusion instanceof GroupsExclusionStrategy)) {
            return null;
        }

        $groups = $groupsExclusion->getGroupsFor($context);
        if ([GroupsExclusionStrategy::DEFAULT_GROUP] === $groups) {
            return null;
        }

        return $groups;
    }

    /**
     * @return bool|null
     */
    private function propertyTypeUsesGroups(array $type)
    {
        if (array_key_exists($type['name'], $this->propertyTypeUseGroupsCache)) {
            return $this->propertyTypeUseGroupsCache[$type['name']];
        }

        try {
            $metadata = $this->driver->loadMetadataForClass($type['name']);

            foreach ($metadata->propertyMetadata as $item) {
                if (null !== $item->groups && $item->groups != [GroupsExclusionStrategy::DEFAULT_GROUP]) {
                    $this->propertyTypeUseGroupsCache[$type['name']] = true;

                    return true;
                }
            }
            $this->propertyTypeUseGroupsCache[$type['name']] = false;

            return false;
        } catch (\ReflectionException $e) {
            $this->propertyTypeUseGroupsCache[$type['name']] = null;

            return null;
        }
    }
}
