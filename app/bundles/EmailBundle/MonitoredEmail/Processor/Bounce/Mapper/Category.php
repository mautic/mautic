<?php

namespace Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\Mapper;

/**
 * Class Category.
 */
class Category
{
    /**
     * @var string
     */
    private $category;

    /**
     * @var string
     */
    private $type;

    /**
     * @var bool
     */
    private $isPermanent;

    /**
     * Category constructor.
     *
     * @param $category
     * @param $type
     * @param $isPermanent
     */
    public function __construct($category, $type, $isPermanent)
    {
        $this->category    = $category;
        $this->type        = $type;
        $this->isPermanent = $isPermanent;

        return $this;
    }

    /**
     * @return string
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return bool
     */
    public function isPermanent()
    {
        return $this->isPermanent;
    }
}
