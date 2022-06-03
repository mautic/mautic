<?php

namespace Mautic\PointBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CategoryBundle\Entity\Category;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\CoreBundle\Helper\IntHelper;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class Point extends FormEntity
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $type;

    /**
     * @var bool
     */
    private $repeatable = false;

    /**
     * @var \DateTime
     */
    private $publishUp;

    /**
     * @var \DateTime
     */
    private $publishDown;

    /**
     * @var int
     */
    private $delta = 0;

    /**
     * @var array
     */
    private $properties = [];

    /**
     * @var ArrayCollection
     */
    private $log;

    /**
     * @var Category
     **/
    private $category;

    public function __clone()
    {
        $this->id = null;

        parent::__clone();
    }

    public function __construct()
    {
        $this->log = new ArrayCollection();
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('points')
            ->setCustomRepositoryClass(PointRepository::class)
            ->addIndex(['type'], 'point_type_search');

        $builder->addIdColumns();

        $builder->createField('type', 'string')
            ->length(50)
            ->build();

        $builder->addPublishDates();

        $builder->createField('repeatable', 'boolean')
            ->build();

        $builder->addField('delta', 'integer');

        $builder->addField('properties', 'array');

        $builder->createOneToMany('log', 'LeadPointLog')
            ->mappedBy('point')
            ->cascadePersist()
            ->cascadeRemove()
            ->fetchExtraLazy()
            ->build();

        $builder->addCategory();
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('name', new Assert\NotBlank([
            'message' => 'mautic.core.name.required',
        ]));

        $metadata->addPropertyConstraint('type', new Assert\NotBlank([
            'message' => 'mautic.point.type.notblank',
        ]));

        $metadata->addPropertyConstraint('delta', new Assert\NotBlank([
            'message' => 'mautic.point.delta.notblank',
        ]));

        $metadata->addPropertyConstraint('delta', new Assert\Range([
            'min' => IntHelper::MIN_INTEGER_VALUE,
            'max' => IntHelper::MAX_INTEGER_VALUE,
        ]));
    }

    /**
     * Prepares the metadata for API usage.
     *
     * @param $metadata
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata)
    {
        $metadata->setGroupPrefix('point')
            ->addListProperties(
                [
                    'id',
                    'name',
                    'category',
                    'type',
                    'description',
                ]
            )
            ->addProperties(
                [
                    'publishUp',
                    'publishDown',
                    'delta',
                    'properties',
                    'repeatable',
                ]
            )
            ->build();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param array $properties
     *
     * @return self
     */
    public function setProperties($properties)
    {
        $this->isChanged('properties', $properties);

        $this->properties = $properties;

        return $this;
    }

    /**
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @param string $type
     *
     * @return self
     */
    public function setType($type)
    {
        $this->isChanged('type', $type);
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return array
     */
    public function convertToArray()
    {
        return get_object_vars($this);
    }

    /**
     * @param string $description
     *
     * @return self
     */
    public function setDescription($description)
    {
        $this->isChanged('description', $description);
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $name
     *
     * @return self
     */
    public function setName($name)
    {
        $this->isChanged('name', $name);
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return self
     */
    public function addLog(LeadPointLog $log)
    {
        $this->log[] = $log;

        return $this;
    }

    public function removeLog(LeadPointLog $log)
    {
        $this->log->removeElement($log);
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getLog()
    {
        return $this->log;
    }

    /**
     * @param \DateTime $publishUp
     *
     * @return Point
     */
    public function setPublishUp($publishUp)
    {
        $this->isChanged('publishUp', $publishUp);
        $this->publishUp = $publishUp;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getPublishUp()
    {
        return $this->publishUp;
    }

    /**
     * @param \DateTime $publishDown
     *
     * @return Point
     */
    public function setPublishDown($publishDown)
    {
        $this->isChanged('publishDown', $publishDown);
        $this->publishDown = $publishDown;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getPublishDown()
    {
        return $this->publishDown;
    }

    /**
     * @return mixed
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param mixed $category
     */
    public function setCategory($category)
    {
        $this->category = $category;
    }

    /**
     * @return mixed
     */
    public function getDelta()
    {
        return $this->delta;
    }

    /**
     * @param mixed $delta
     */
    public function setDelta($delta)
    {
        $this->delta = (int) $delta;
    }

    /**
     * @param bool $repeatable
     *
     * @return Point
     */
    public function setRepeatable($repeatable)
    {
        $this->isChanged('repeatable', $repeatable);
        $this->repeatable = $repeatable;

        return $this;
    }

    /**
     * @return bool
     */
    public function getRepeatable()
    {
        return $this->repeatable;
    }
}
