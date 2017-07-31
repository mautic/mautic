<?php

namespace  MauticPlugin\MauticCrmBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\LeadBundle\Entity\Lead;
use  MauticPlugin\MauticCrmBundle\Entity\PipedriveDeal;
use  MauticPlugin\MauticCrmBundle\Entity\PipedriveProduct;

class PipedriveDealProduct
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var PipedriveDeal
     */
    private $deal;

    /**
     * @var PipedriveProduct
     */
    private $product;

    /**
     * @var int
     */
    private $itemPrice;

    /**
     * @var quantity
     */
    private $quantity;


    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('plugin_crm_pipedrive_deal_product')
            ->addUniqueConstraint(['deal_id', 'product_id'], 'unique_deal_product');

        $builder->addId();
        $builder->addNamedField('itemPrice', 'integer', 'item_price', false);
        $builder->addNamedField('quantity', 'integer', 'quantity', false);

        $deal = $builder->createManyToOne('deal', 'MauticPlugin\MauticCrmBundle\Entity\PipedriveDeal')
              ->inversedBy('dealProducts')
              ->cascadeMerge()
              ->addJoinColumn('deal_id', 'id', $nullable = false, $unique = false, $onDelete = 'CASCADE')
              ->build();

        $product = $builder->createManyToOne('product', 'MauticPlugin\MauticCrmBundle\Entity\PipedriveProduct')
                 ->addJoinColumn('product_id', 'id', $nullable = false, $unique = false, $onDelete = 'CASCADE')
                 ->build();
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
    public function getDeal()
    {
        return $this->deal;
    }

    /**
     * @param PipedriveDeal $deal
     *
     * @return PipedriveDealProduct
     */
    public function setDeal(PipedriveDeal $deal)
    {
        $this->deal = $deal;

        return $this;
    }


    /**
     * @param PipedriveProduct $product
     *
     * @return PipedriveDealProduct
     */
    public function setProduct(PipedriveProduct $product)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * Get product.
     *
     * @return PipedriveProduct
     */
    public function getProduct()
    {
        return $this->product ;
    }

    /**
     * @param integer $itemPrice
     *
     * @return PipedriveDealProduct
     */
    public function setItemPrice($itemPrice)
    {
        $this->itemPrice = $itemPrice;

        return $this;
    }

    /**
     * Get itemPrice.
     *
     * @return int
     */
    public function getItemPrice()
    {
        return $this->itemPrice ;
    }

    /**
     * @param int $quantity
     *
     * @return PipedriveDealProduct
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Get quantity.
     *
     * @return int
     */
    public function getQuantity()
    {
        return $this->quantity ;
    }


}
