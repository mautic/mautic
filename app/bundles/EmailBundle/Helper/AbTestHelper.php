<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Helper;

use Mautic\EmailBundle\Entity\Email;

class AbTestHelper
{
    /**
     * Determines the winner of A/B test based on open rate.
     *
     * @param $factory
     * @param $parent
     * @param $children
     *
     * @return array
     */
    public static function determineOpenRateWinner($factory, $parent, $children)
    {
        /** @var \Mautic\EmailBundle\Entity\StatRepository $repo */
        $repo = $factory->getEntityManager()->getRepository('MauticEmailBundle:Stat');
        /** @var Email $parent */
        $ids       = $parent->getRelatedEntityIds();
        $startDate = $parent->getVariantStartDate();

        if ($startDate != null && !empty($ids)) {
            //get their bounce rates
            $counts = $repo->getOpenedRates($ids, $startDate);

            $translator = $factory->getTranslator();
            if ($counts) {
                $rates      = $support      = $data      = [];
                $hasResults = [];

                $parentId = $parent->getId();
                foreach ($counts as $id => $stats) {
                    if ($id !== $parentId && !array_key_exists($id, $children)) {
                        continue;
                    }
                    $name = ($parentId === $id) ? $parent->getName()
                        : $children[$id]->getName();
                    $support['labels'][]                                            = $name.' ('.$stats['readRate'].'%)';
                    $rates[$id]                                                     = $stats['readRate'];
                    $data[$translator->trans('mautic.email.abtest.label.opened')][] = $stats['readCount'];
                    $data[$translator->trans('mautic.email.abtest.label.sent')][]   = $stats['totalCount'];
                    $hasResults[]                                                   = $id;
                }

                if (!in_array($parent->getId(), $hasResults)) {
                    //make sure that parent and published children are included
                    $support['labels'][] = $parent->getName().' (0%)';

                    $data[$translator->trans('mautic.email.abtest.label.opened')][] = 0;
                    $data[$translator->trans('mautic.email.abtest.label.sent')][]   = 0;
                }

                foreach ($children as $c) {
                    if ($c->isPublished()) {
                        if (!in_array($c->getId(), $hasResults)) {
                            //make sure that parent and published children are included
                            $support['labels'][]                                            = $c->getName().' (0%)';
                            $data[$translator->trans('mautic.email.abtest.label.opened')][] = 0;
                            $data[$translator->trans('mautic.email.abtest.label.sent')][]   = 0;
                        }
                    }
                }
                $support['data'] = $data;

                //set max for scales
                $maxes = [];
                foreach ($support['data'] as $label => $data) {
                    $maxes[] = max($data);
                }
                $top                   = max($maxes);
                $support['step_width'] = (ceil($top / 10) * 10);

                //put in order from least to greatest just because
                asort($rates);

                //who's the winner?
                $max = max($rates);

                //get the page ids with the most number of downloads
                $winners = ($max > 0) ? array_keys($rates, $max) : [];

                return [
                    'winners'         => $winners,
                    'support'         => $support,
                    'basedOn'         => 'email.openrate',
                    'supportTemplate' => 'MauticPageBundle:SubscribedEvents\AbTest:bargraph.html.php',
                ];
            }
        }

        return [
            'winners' => [],
            'support' => [],
            'basedOn' => 'email.openrate',
        ];
    }

    /**
     * Determines the winner of A/B test based on clickthrough rates.
     *
     * @param $factory
     * @param $parent
     * @param $children
     *
     * @return array
     */
    public static function determineClickthroughRateWinner($factory, $parent, $children)
    {
        /** @var \Mautic\PageBundle\Entity\HitRepository $pageRepo */
        $pageRepo = $factory->getEntityManager()->getRepository('MauticPageBundle:Hit');
        /** @var \Mautic\EmailBundle\Entity\StatRepository $emailRepo */
        $emailRepo = $factory->getEntityManager()->getRepository('MauticEmailBundle:Stat');
        /** @var Email $parent */
        $ids = $parent->getRelatedEntityIds();

        $startDate = $parent->getVariantStartDate();
        if ($startDate != null && !empty($ids)) {
            //get their bounce rates
            $clickthroughCounts = $pageRepo->getEmailClickthroughHitCount($ids, $startDate);
            $sentCounts         = $emailRepo->getSentCounts($ids, $startDate);

            $translator = $factory->getTranslator();
            if ($clickthroughCounts) {
                $rates      = $support      = $data      = [];
                $hasResults = [];

                $parentId = $parent->getId();
                foreach ($clickthroughCounts as $id => $count) {
                    if ($id !== $parentId && !array_key_exists($id, $children)) {
                        continue;
                    }
                    if (!isset($sentCounts[$id])) {
                        $sentCounts[$id] = 0;
                    }

                    $rates[$id] = $sentCounts[$id] ? round(($count / $sentCounts[$id]) * 100, 2) : 0;

                    $name                = ($parentId === $id) ? $parent->getName() : $children[$id]->getName();
                    $support['labels'][] = $name.' ('.$rates[$id].'%)';

                    $data[$translator->trans('mautic.email.abtest.label.clickthrough')][] = $count;
                    $data[$translator->trans('mautic.email.abtest.label.opened')][]       = $sentCounts[$id];
                    $hasResults[]                                                         = $id;
                }

                if (!in_array($parent->getId(), $hasResults)) {
                    //make sure that parent and published children are included
                    $support['labels'][] = $parent->getName().' (0%)';

                    $data[$translator->trans('mautic.email.abtest.label.clickthrough')][] = 0;
                    $data[$translator->trans('mautic.email.abtest.label.opened')][]       = 0;
                }

                foreach ($children as $c) {
                    if ($c->isPublished()) {
                        if (!in_array($c->getId(), $hasResults)) {
                            //make sure that parent and published children are included
                            $support['labels'][]                                                  = $c->getName().' (0%)';
                            $data[$translator->trans('mautic.email.abtest.label.clickthrough')][] = 0;
                            $data[$translator->trans('mautic.email.abtest.label.opened')][]       = 0;
                        }
                    }
                }
                $support['data'] = $data;

                //set max for scales
                $maxes = [];
                foreach ($support['data'] as $label => $data) {
                    $maxes[] = max($data);
                }
                $top                   = max($maxes);
                $support['step_width'] = (ceil($top / 10) * 10);

                //put in order from least to greatest just because
                asort($rates);

                //who's the winner?
                $max = max($rates);

                //get the page ids with the most number of downloads
                $winners = ($max > 0) ? array_keys($rates, $max) : [];

                return [
                    'winners'         => $winners,
                    'support'         => $support,
                    'basedOn'         => 'email.clickthrough',
                    'supportTemplate' => 'MauticPageBundle:SubscribedEvents\AbTest:bargraph.html.php',
                ];
            }
        }

        return [
            'winners' => [],
            'support' => [],
            'basedOn' => 'email.clickthrough',
        ];
    }
}
