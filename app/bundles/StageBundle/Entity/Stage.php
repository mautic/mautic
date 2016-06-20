<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\StageBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Class Stage
 *
 * @package Mautic\StageBundle\Entity
 */
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
     * @var string
     */
    private $type;

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
     * @var array
     */
    private $properties = array();

    /**
     * @var ArrayCollection
     */
    private $log;

    /**
     * @var \Mautic\CategoryBundle\Entity\Category
     **/
    private $category;

    public function __clone()
    {
        $this->id = null;

        parent::__clone();
    }

    /**
     * Construct
     */
    public function __construct()
    {
        $this->properties = new ArrayCollection();
        $this->log = new ArrayCollection();
    }

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata (ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('stages')
            ->setCustomRepositoryClass('Mautic\StageBundle\Entity\StageRepository')
            ->addIndex(array('type'), 'stage_type_search');

        $builder->addIdColumns();

        $builder->createField('type', 'string')
            ->length(50)
            ->build();

        $builder->createField('weight', 'integer')
            ->build();

        $builder->addPublishDates();

        $builder->addField('properties', 'array');

        $builder->createOneToMany('log', 'LeadStageLog')
            ->mappedBy('stage')
            ->cascadePersist()
            ->cascadeRemove()
            ->fetchExtraLazy()
            ->build();

        $builder->addCategory();
    }

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata (ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('name', new Assert\NotBlank(array(
            'message' => 'mautic.core.name.required'
        )));

        $metadata->addPropertyConstraint('type', new Assert\NotBlank(array(
            'message' => 'mautic.stage.type.notblank'
        )));
    }

    /**
     * Prepares the metadata for API usage
     *
     * @param $metadata
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata)
    {
        $metadata->setGroupPrefix('stage')
            ->addListProperties(
                array(
                    'id',
                    'name',
                    'category',
                    'type',
                    'weight',
                    'description'
                )
            )
            ->addProperties(
                array(
                    'publishUp',
                    'publishDown',
                    'properties'
                )
            )
            ->build();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId ()
    {
        return $this->id;
    }

    /**
     * Set properties
     *
     * @param array $properties
     *
     * @return array
     */
    public function setProperties ($properties)
    {
        $this->isChanged('properties', $properties);

        $this->properties = $properties;

        return $this;
    }

    /**
     * Get properties
     *
     * @return array
     */
    public function getProperties ()
    {
        return $this->properties;
    }

    /**
     * Set type
     *
     * @param string $type
     *
     * @return string
     */
    public function setType ($type)
    {
        $this->isChanged('type', $type);
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType ()
    {
        return $this->type;
    }

    /**
     * Set weight
     *
     * @param integer $weight
     *
     * @return integer
     */
    public function setWeight ($type)
    {
        $this->weight = (int)$type;

        return $this;
    }

    /**
     * Get weight
     *
     * @return integer
     */
    public function getWeight ()
    {
        return $this->weight;
    }

    /**
     * @return array
     */
    public function convertToArray ()
    {
        return get_object_vars($this);
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return string
     */
    public function setDescription ($description)
    {
        $this->isChanged('description', $description);
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription ()
    {
        return $this->description;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return string
     */
    public function setName ($name)
    {
        $this->isChanged('name', $name);
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName ()
    {
        return $this->name;
    }

    /**
     * Add log
     *
     * @param LeadStageLog $log
     *
     * @return Log
     */
    public function addLog (LeadStageLog $log)
    {
        $this->log[] = $log;

        return $this;
    }

    /**
     * Remove log
     *
     * @param LeadStageLog $log
     */
    public function removeLog (LeadStageLog $log)
    {
        $this->log->removeElement($log);
    }

    /**
     * Get log
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getLog ()
    {
        return $this->log;
    }

    /**
     * Set publishUp
     *
     * @param \DateTime $publishUp
     *
     * @return Stage
     */
    public function setPublishUp ($publishUp)
    {
        $this->isChanged('publishUp', $publishUp);
        $this->publishUp = $publishUp;

        return $this;
    }

    /**
     * Get publishUp
     *
     * @return \DateTime
     */
    public function getPublishUp ()
    {
        return $this->publishUp;
    }

    /**
     * Set publishDown
     *
     * @param \DateTime $publishDown
     *
     * @return Stage
     */
    public function setPublishDown ($publishDown)
    {
        $this->isChanged('publishDown', $publishDown);
        $this->publishDown = $publishDown;

        return $this;
    }

    /**
     * Get publishDown
     *
     * @return \DateTime
     */
    public function getPublishDown ()
    {
        return $this->publishDown;
    }

    /**
     * @return mixed
     */
    public function getCategory ()
    {
        return $this->category;
    }

    /**
     * @param mixed $category
     */
    public function setCategory ($category)
    {
        $this->category = $category;
    }
}
