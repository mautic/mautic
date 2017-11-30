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
 * Class CategoryTypesEvent.
 */
class CategoryTypesEvent extends CommonEvent
{
    /**
     * @var array
     */
    protected $types = [];

    /**
     * Returns the array of Category Types.
     *
     * @return array
     */
    public function getCategoryTypes()
    {
        if (!array_key_exists('global', $this->types)) {
            // Alphabetize once
            asort($this->types);

            $this->types = array_merge(
                ['global' => 'mautic.category.global'],
                $this->types
            );
        }

        return $this->types;
    }

    /**
     * Adds the category type and label.
     *
     * @param string $type
     * @param string $label
     */
    public function addCategoryType($type, $label = null)
    {
        if ($label === null) {
            $label = 'mautic.'.$type.'.'.$type;
        }

        $this->types[$type] = $label;
    }
}
