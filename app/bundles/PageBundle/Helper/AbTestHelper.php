<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\PageBundle\Entity\Page;

/**
 * Class AbTestHelper
 */
class AbTestHelper
{

    /**
     * Determines the winner of A/B test based on bounce rates
     *
     * @param MauticFactory $factory
     * @param Page          $parent
     * @param               $children
     *
     * @return array
     */
    public static function determineBounceTestWinner($factory, $parent, $children)
    {
        //find the hits that did not go any further
        $repo = $factory->getEntityManager()->getRepository('MauticPageBundle:Hit');

        $pageIds = array($parent->getId());

        foreach ($children as $c) {
            if ($c->isPublished()) {
                $id              = $c->getId();
                $pageIds[]       = $id;
            }
        }

        $startDate = $parent->getVariantStartDate();
        if ($startDate != null && !empty($pageIds)) {
            //get their bounce rates
            $counts = $repo->getBounces($pageIds, $startDate);

            if ($counts) {
                //let's arrange by rate
                $rates = array();
                foreach ($counts as $pid => $stats) {
                    $rates[$pid] = $stats['rate'];
                }

                //put in order from least to greatest just because
                asort($rates);

                //who's the winner?
                $min = min($rates);

                //get the page ids with the least number of bounces
                $winners = array_keys($rates, $min);

                return array(
                    'winners'         => $winners,
                    'support'         => $counts,
                    'basedOn'         => 'page.bouncerate',
                    'supportTemplate' => 'MauticPageBundle:AbTest:bounces.html.php'
                );
            }
        }

        return array(
            'winners' => array(),
            'support' => array(),
            'basedOn' => 'page.bouncerate'
        );
    }

    /**
     * Determines the winner of A/B test based on dwell time rates
     *
     * @param MauticFactory $factory
     * @param Page          $parent
     * @param               $children
     *
     * @return array
     */
    public static function determineDwellTimeTestWinner($factory, $parent, $children)
    {
        //find the hits that did not go any further
        $repo = $factory->getEntityManager()->getRepository('MauticPageBundle:Hit');

        $pageIds  = array($parent->getId());

        foreach ($children as $c) {
            $pageIds[] = $c->getId();
        }

        $startDate = $parent->getVariantStartDate();
        if ($startDate != null && !empty($pageIds)) {
            //get their bounce rates
            $counts = $repo->getDwellTimes($pageIds, $startDate);

            if ($counts) {
                //in order to get a fair grade, we have to compare the averages here since a page that is only shown
                //25% of the time will have a significantly lower sum than a page shown 75% of the time
                $avgs = array();
                foreach ($counts as $pid => $stats) {
                    $avgs[$pid] = $stats['average'];
                }

                //find the max
                $max = max($avgs);

                //get the page ids with the greatest average dwell time
                $winners = array_keys($avgs, $max);

                return array(
                    'winners'         => $winners,
                    'support'         => $counts,
                    'basedOn'         => 'page.dwelltime',
                    'supportTemplate' => 'MauticPageBundle:AbTest:dwelltimes.html.php'
                );
            }
        }

        return array(
            'winners' => array(),
            'support' => array(),
            'basedOn' => 'page.dwelltime'
        );
    }
}
