<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Helper;

class AbTestHelper
{

    /**
     * Determines the winner of A/B test based on open rate
     *
     * @param $factory
     * @param $parent
     * @param $properties
     *
     * @return array
     */
    public static function determineOpenRateWinner($factory, $parent, $children)
    {
        //find the hits that did not go any further
        $repo = $factory->getEntityManager()->getRepository('MauticEmailBundle:Stat');

        $ids = array($parent->getId());

        foreach ($children as $c) {
            if ($c->isPublished()) {
                $ids[] = $c->getId();
            }
        }

        $startDate = $parent->getVariantStartDate();
        if ($startDate != null && !empty($ids)) {
            //get their bounce rates
            $counts = $repo->getOpenedRates($ids, $startDate);

            if ($counts) {
                $rates = array();
                foreach ($counts as $id => $stats) {
                    $rates[$id] = $stats['readRate'];
                }

                //put in order from least to greatest just because
                asort($rates);

                //who's the winner?
                $max = max($rates);

                //get the ids with the greated rate
                $winners = array_keys($rates, $max);

                return array(
                    'winners'         => $winners,
                    'support'         => $counts,
                    'basedOn'         => 'email.openrate',
                    'supportTemplate' => 'MauticEmailBundle:AbTest:openrate.html.php'
                );
            }
        }

        return array(
            'winners' => array(),
            'support' => array(),
            'basedOn' => 'email.openrate'
        );
    }
}