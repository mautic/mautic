<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
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

            $translator = $factory->getTranslator();
            if ($counts) {
                $rates  = $support = $data = array();
                $hasResults = array();

                $parentId = $parent->getId();
                foreach ($counts as $id => $stats) {
                    $subject = ($parentId === $id) ? $parent->getSubject() : $children[$id]->getSubject();
                    $support['labels'][] = $id . ':' . $subject;
                    $rates[$id]                                                      = $stats['readRate'];
                    $data[$translator->trans('mautic.email.abtest.label.opened')][]  = $stats['readCount'];
                    $data[$translator->trans('mautic.email.abtest.label.sent')][]    = $stats['totalCount'];
                    $data[$translator->trans('mautic.email.abtest.label.rates')][]   = $stats['readRate'];
                    $hasResults[]                                                    = $id;
                }

                if (!in_array($parent->getId(), $hasResults)) {
                    //make sure that parent and published children are included
                    $support['labels'][] = $parent->getId() . ':' . $parent->getSubject();

                    $data[$translator->trans('mautic.email.abtest.label.opened')][]   = 0;
                    $data[$translator->trans('mautic.email.abtest.label.sent')][]      = 0;
                    $data[$translator->trans('mautic.email.abtest.label.rates')][]     = 0;
                }

                foreach ($children as $c) {
                    if ($c->isPublished()) {
                        if (!in_array($c->getId(), $hasResults)) {
                            //make sure that parent and published children are included
                            $support['labels'][] = $c->getId() . ':' . $c->getSubject();
                            $data[$translator->trans('mautic.email.abtest.label.opened')][] = 0;
                            $data[$translator->trans('mautic.email.abtest.label.sent')][]      = 0;
                            $data[$translator->trans('mautic.email.abtest.label.rates')][]     = 0;
                        }
                    }
                }
                $support['data'] = $data;

                //set max for scales
                $maxes = array();
                foreach ($support['data'] as $label => $data) {
                    $maxes[] = max($data);
                }
                $top                   = max($maxes);
                $support['step_width'] = (floor($top / 10) * 10) / 10;

                //put in order from least to greatest just because
                asort($rates);

                //who's the winner?
                $max = max($rates);

                //get the page ids with the most number of downloads
                $winners = ($max > 0) ? array_keys($rates, $max) : array();

                return array(
                    'winners'         => $winners,
                    'support'         => $support,
                    'basedOn'         => 'email.openrate',
                    'supportTemplate' => 'MauticPageBundle:SubscribedEvents\AbTest:bargraph.html.php'
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