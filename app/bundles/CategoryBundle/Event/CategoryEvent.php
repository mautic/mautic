<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CategoryBundle\Event;

use Mautic\CategoryBundle\Entity\Category;
use Mautic\CoreBundle\Event\CommonEvent;

/**
 * Class CategoryEvent.
 */
class CategoryEvent extends CommonEvent
{
    /**
     * @param Category $category
     * @param bool     $isNew
     */
    public function __construct(Category &$category, $isNew = false)
    {
        $this->entity = &$category;
        $this->isNew  = $isNew;
    }

    /**
     * Returns the Category entity.
     *
     * @return Category
     */
    public function getCategory()
    {
        return $this->entity;
    }

    /**
     * Sets the Category entity.
     *
     * @param Category $category
     */
    public function setCategory(Category $category)
    {
        $this->entity = $category;
    }
}
