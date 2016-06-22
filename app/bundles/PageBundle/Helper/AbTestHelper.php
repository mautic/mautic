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
            $counts     = $repo->getBounces($pageIds, $startDate);
            if ($counts) {
                //let's arrange by rate
                $rates             = array();
                $support['data']   = array();
                $support['labels'] = array();
                $bounceLabel       = $factory->getTranslator()->trans('mautic.page.abtest.label.bounces');

                foreach ($counts as $pid => $stats) {
                    $rates[$pid]                     = $stats['rate'];
                    $support['data'][$bounceLabel][] = $rates[$pid];
                    $support['labels'][]             = $pid . ':' . $stats['title'];
                }

                $max                   = max($rates);
                $support['step_width'] = (ceil($max / 10) * 10);

                //get the page ids with the greatest average dwell time
                $winners = ($max > 0) ? array_keys($rates, $max) : array();

                return array(
                    'winners'         => $winners,
                    'support'         => $support,
                    'basedOn'         => 'page.bouncerate',
                    'supportTemplate' => 'MauticPageBundle:SubscribedEvents\AbTest:bargraph.html.php'
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
            $counts = $repo->getDwellTimesForPages($pageIds, array('fromDate' => $startDate));
            $translator = $factory->getTranslator();
            $support = array();

            if ($counts) {
                //in order to get a fair grade, we have to compare the averages here since a page that is only shown
                //25% of the time will have a significantly lower sum than a page shown 75% of the time
                $avgs              = array();
                $support['data']   = array();
                $support['labels'] = array();
                foreach ($counts as $pid => $stats) {
                    $avgs[$pid] = $stats['average'];
                    $support['data'][$translator->trans('mautic.page.abtest.label.dewlltime.average')][] = $stats['average'];
                    $support['labels'][] = $pid . ':' . $stats['title'];
                }

                //set max for scales
                $max                   = max($avgs);
                $support['step_width'] = (ceil($max / 10) * 10);

                //get the page ids with the greatest average dwell time
                $winners = ($max > 0) ? array_keys($avgs, $max) : array();

                return array(
                    'winners'         => $winners,
                    'support'         => $support,
                    'basedOn'         => 'page.dwelltime',
                    'supportTemplate' => 'MauticPageBundle:SubscribedEvents\AbTest:bargraph.html.php'
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
