<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CategoryBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\CategoryBundle\Entity\Category;

/**
 * Class CategoryBundlesEvent
 *
 * @package Mautic\CategoryBundle\Event
 */
class CategoryBundlesEvent extends CommonEvent
{
    /**
     * @var array $bundles
     */
    protected $bundles = array();

    /**
     * Returns the array of Category Types
     *
     * @return array
     */
    public function getCategoryTypes()
    {
        asort($this->bundles);
        return $this->bundles;
    }

    /**
     * Adds the category type and label
     *
     * @param string $bundle
     * @param string $label
     *
     * @return void
     */
    public function addCategoryType($bundle, $label = null)
    {
        if ($label === null) {
            $label = 'mautic.' . $bundle . '.' . $bundle;
        }

        $this->bundles[$bundle] = $label;
    }
}
