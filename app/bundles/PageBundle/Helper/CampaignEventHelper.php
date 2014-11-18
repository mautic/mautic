<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\PageBundle\Entity\Page;

/**
 * Class CampaignEventHelper
 */
class CampaignEventHelper
{
    /**
     * @param MauticFactory $factory
     * @param $eventDetails
     * @param $event
     *
     * @return bool
     */
    public static function onPageHit($factory, $eventDetails, $event)
    {
        if ($eventDetails == null) {
            return true;
        }

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

        $limitToPages = $event['properties']['pages'];

        if (!empty($limitToPages) && !in_array($pageHitId, $limitToPages)) {
            return false;
        }

        return true;
    }
}
