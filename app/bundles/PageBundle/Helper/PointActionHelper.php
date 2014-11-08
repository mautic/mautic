<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Helper;
use Mautic\PageBundle\Entity\Page;

/**
 * Class PointActionHelper
 *
 * @package Mautic\PageBundle\Helper
 */
class PointActionHelper
{

    /**
     * @param $factory
     * @param $eventDetails
     * @param $action
     *
     * @return int
     */
    public static function onPageHit($factory, $eventDetails, $action)
    {

        $pageHit = $eventDetails->getPage();

        if ($pageHit instanceof Page) {
            /** @var \Mautic\PageBundle\Model\PageModel $pageModel */
            $pageModel = $factory->getModel('page');
            list($parent, $children)  = $pageModel->getVariants($pageHit);
            //use the parent (self or configured parent)
            $pageHitId = $parent->getId();
        } else {
            $pageHitId = 0;
        }

        $limitToPages = $action['properties']['pages'];

        if (!empty($limitToPages) && !in_array($pageHitId, $limitToPages)) {
            //no points change
            return 0;
        }

        return $action['properties']['delta'];
    }
}