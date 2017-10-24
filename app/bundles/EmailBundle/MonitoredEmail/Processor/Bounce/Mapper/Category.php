<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\Mapper;

/**
 * Class Category.
 */
class Category
{
    /**
     * @var string
     */
    protected $category;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var bool
     */
    protected $isPermanent;

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
