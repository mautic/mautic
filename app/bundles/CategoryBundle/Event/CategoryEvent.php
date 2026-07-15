<?php

namespace Mautic\CategoryBundle\Event;

use Mautic\CategoryBundle\Entity\Category;
use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\CoreBundle\Event\DependencyErrorEventInterface;
use Mautic\CoreBundle\Event\DependencyErrorEventTrait;

class CategoryEvent extends CommonEvent implements DependencyErrorEventInterface
{
    use DependencyErrorEventTrait;

    /**
     * @param bool $isNew
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
     */
    public function setCategory(Category $category): void
    {
        $this->entity = $category;
    }
}
