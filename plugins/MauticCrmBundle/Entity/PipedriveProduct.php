<?php

namespace  MauticPlugin\MauticCrmBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class PipedriveProduct
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var int
     */
    private $productId;

    /**
     * @var string
     */
    private $name;

    /**
     * @var boolean
     */
    private $active;

    /**
     * @var boolean
     */
    private $selectable;

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('plugin_crm_pipedrive_products')
            ->addUniqueConstraint(['product_id'], 'unique_product');

        $builder->addId();
        $builder->addNamedField('productId', 'integer', 'product_id', false);
        $builder->addNamedField('name', 'string', 'name', false);
        $builder->addNamedField('active', 'boolean', 'active', false);
        $builder->addNamedField('selectable', 'boolean', 'selectable', false);
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return integer
     */
    public function getProductId()
    {
        return $this->productId;
    }

    /**
     * @param integer $productId
     *
     * @return PipedriveProduct
     */
    public function setProductId($productId)
    {
        $this->productId = $productId;

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
     * @param string $name
     *
     * @return PipedriveProduct
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @param boolean $active
     *
     * @return PipedriveProduct
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }


    /**
     * @return integer
     */
    public function isSelectable()
    {
        return $this->selectable;
    }

    /**
     * @param integer $selectable
     *
     * @return PipedriveProduct
     */
    public function setSelectable($selectable)
    {
        $this->selectable = $selectable;

        return $this;
    }
}
