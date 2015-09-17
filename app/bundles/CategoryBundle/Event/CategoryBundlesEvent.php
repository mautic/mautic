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
     * Returns the array of Bundles
     *
     * @return array
     */
    public function getBundles()
    {
        asort($this->bundles);
        return $this->bundles;
    }

    /**
     * Adds the bundle entity
     *
     * @param string $bundle
     */
    public function addBundle($bundle, $label = null)
    {
        if (!$label) {
            $label = 'mautic.' . $bundle . '.' . $bundle;
        }
        
        $this->bundles[$bundle] = $label;
    }
}
