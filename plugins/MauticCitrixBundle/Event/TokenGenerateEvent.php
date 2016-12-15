<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCitrixBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;

/**
 * Class TokenGenerateEvent.
 */
class TokenGenerateEvent extends CommonEvent
{
    /**
     * @var array
     */
    private $params = [];

    /**
     * TokenGenerateEvent constructor.
     *
     * @param array $params
     */
    public function __construct(array $params)
    {
        $this->params = $params;
    }

    /**
     * Returns the params array.
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @param array $params
     */
    protected function setParams($params)
    {
        $this->params = $params;
    }

    /**
     * @return string
     */
    public function getProduct()
    {
        return array_key_exists('product', $this->params) ? $this->params['product'] : '';
    }

    /**
     * @param string $product
     */
    public function setProduct($product)
    {
        $this->params['product'] = $product;
    }

    /**
     * @return string
     */
    public function getProductLink()
    {
        return array_key_exists('productLink', $this->params) ? $this->params['productLink'] : '';
    }

    /**
     * @param string $productLink
     */
    public function setProductLink($productLink)
    {
        $this->params['productLink'] = $productLink;
    }

    /**
     * @return string
     */
    public function getProductText()
    {
        return array_key_exists('productText', $this->params) ? $this->params['productText'] : '';
    }

    /**
     * @param string $productText
     */
    public function setProductText($productText)
    {
        $this->params['productText'] = $productText;
    }
}
