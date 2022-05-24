<?php

namespace Mautic\StageBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CategoryBundle\Entity\Category;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class Stage extends FormEntity
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
     * @var int
     */
    private $weight = 0;

    /**
     * @var \DateTime
     */
    private $publishUp;

    /**
     * @var \DateTime
     */
    private $publishDown;

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
        $builder->setTable('stages')
            ->setCustomRepositoryClass('Mautic\StageBundle\Entity\StageRepository');

        $builder->addIdColumns();

        $builder->createField('weight', 'integer')
            ->build();

        $builder->addPublishDates();

        $builder->createOneToMany('log', 'LeadStageLog')
            ->mappedBy('stage')
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
    }

    /**
     * Prepares the metadata for API usage.
     *
     * @param $metadata
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata)
    {
        $metadata->setGroupPrefix('stage')
            ->addListProperties(
                [
                    'id',
                    'name',
                    'category',
                    'weight',
                    'description',
                ]
            )
            ->addProperties(
                [
                    'publishUp',
                    'publishDown',
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
     * @return int
     */
    public function setWeight($type)
    {
        $this->weight = (int) $type;

        return $this;
    }

    /**
     * @return int
     */
    public function getWeight()
    {
        return $this->weight;
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
     * @return string
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
     * @return string
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
     * @return Log
     */
    public function addLog(LeadStageLog $log)
    {
        $this->log[] = $log;

        return $this;
    }

    public function removeLog(LeadStageLog $log)
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
     * @return Stage
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
     * @return Stage
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
     * @return Category|null
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param Category|null $category
     */
    public function setCategory($category)
    {
        $this->category = $category;
    }
}
