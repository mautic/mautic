<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DashboardBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Class Module
 *
 * @package Mautic\DashboardBundle\Entity
 */
class Module extends FormEntity
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
     * @var integer
     */
    private $width;

    /**
     * @var integer
     */
    private $height;

    /**
     * @var integer
     */
    private $ordering;

    /**
     * @var string
     */
    private $type;

    /**
     * @var array
     */
    private $params = array();

    public function __clone()
    {
        $this->id = null;

        parent::__clone();
    }

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata (ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('modules')
            ->setCustomRepositoryClass('Mautic\DashboardBundle\Entity\ModuleRepository');

        $builder->addIdColumns();

        $builder->addField('type', 'string');
        $builder->addField('width', 'integer');
        $builder->addField('height', 'integer');
        $builder->addField('ordering', 'integer');

        $builder->createField('params', 'array')
            ->nullable()
            ->build();
    }

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata (ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('name', new NotBlank(array(
            'message' => 'mautic.core.name.required'
        )));
    }

    /**
     * Set id
     *
     * @param integer $id
     */
    public function setId($id)
    {
        $this->isChanged('id', $id);
        $this->id = $id;

        return $this;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Report
     */
    public function setName($name)
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
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set type
     *
     * @param string $type
     *
     * @return Report
     */
    public function setType($type)
    {
        $this->isChanged('type', $type);
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return integer
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set width
     *
     * @param integer $width
     *
     * @return Report
     */
    public function setWidth($width)
    {
        $this->isChanged('width', $width);
        $this->width = $width;

        return $this;
    }

    /**
     * Get width
     *
     * @return integer
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Set height
     *
     * @param integer $height
     *
     * @return Report
     */
    public function setHeight($height)
    {
        $this->isChanged('height', $height);
        $this->height = $height;

        return $this;
    }

    /**
     * Get height
     *
     * @return integer
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * Set ordering
     *
     * @param integer $ordering
     *
     * @return Report
     */
    public function setOrdering($ordering)
    {
        $this->isChanged('ordering', $ordering);
        $this->ordering = $ordering;

        return $this;
    }

    /**
     * Get ordering
     *
     * @return integer
     */
    public function getOrdering()
    {
        return $this->ordering;
    }

    /**
     * Get params
     *
     * @return array $params
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Set params
     *
     * @param array $params
     *
     * @return Report
     */
    public function setParams(array $params)
    {
        $this->isChanged('params', $params);
        $this->params = $params;

        return $this;
    }
}