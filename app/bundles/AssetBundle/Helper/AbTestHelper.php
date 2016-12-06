<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\EmailBundle\Entity\Email;
use Mautic\PageBundle\Entity\Page;

/**
 * Class AbTestHelper.
 */
class AbTestHelper
{
    /**
     * Determines the winner of A/B test based on number of asset downloads.
     *
     * @param MauticFactory $factory
     * @param Page          $parent
     * @param               $children
     *
     * @return array
     */
    public static function determineDownloadWinner($factory, $parent, $children)
    {
        $repo = $factory->getEntityManager()->getRepository('MauticAssetBundle:Download');

        //if this is an email A/B test, then link email to page to form submission
        //if it is a page A/B test, then link form submission to page
        $type = ($parent instanceof Email) ? 'email' : 'page';

        $ids = [$parent->getId()];

        foreach ($children as $c) {
            if ($c->isPublished()) {
                $id    = $c->getId();
                $ids[] = $id;
            }
        }

        $startDate = $parent->getVariantStartDate();
        if ($startDate != null && !empty($ids)) {
            $counts = ($type == 'page') ? $repo->getDownloadCountsByPage($ids, $startDate) : $repo->getDownloadCountsByEmail($ids, $startDate);

            $translator = $factory->getTranslator();
            if ($counts) {
                $downloads  = $support  = $data  = [];
                $hasResults = [];

                $downloadsLabel = $translator->trans('mautic.asset.abtest.label.downloads');
                $hitsLabel      = ($type == 'page') ? $translator->trans('mautic.asset.abtest.label.hits') : $translator->trans('mautic.asset.abtest.label.sentemils');
                foreach ($counts as $stats) {
                    $rate                    = ($stats['total']) ? round(($stats['count'] / $stats['total']) * 100, 2) : 0;
                    $downloads[$stats['id']] = $rate;
                    $data[$downloadsLabel][] = $stats['count'];
                    $data[$hitsLabel][]      = $stats['total'];
                    $support['labels'][]     = $stats['id'].':'.$stats['name'].' ('.$rate.'%)';
                    $hasResults[]            = $stats['id'];
                }

                //make sure that parent and published children are included
                if (!in_array($parent->getId(), $hasResults)) {
                    $data[$downloadsLabel][] = 0;
                    $data[$hitsLabel][]      = 0;
                    $support['labels'][]     = $parent->getId().':'.(($type == 'page') ? $parent->getTitle() : $parent->getName()).' (0%)';
                }

                foreach ($children as $c) {
                    if ($c->isPublished()) {
                        if (!in_array($c->getId(), $hasResults)) {
                            $data[$downloadsLabel][] = 0;
                            $data[$hitsLabel][]      = 0;
                            $support['labels'][]     = $c->getId().':'.(($type == 'page') ? $c->getTitle() : $c->getName()).' (0%)';
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
                asort($downloads);

                //who's the winner?
                $max = max($downloads);

                //get the page ids with the most number of downloads
                $winners = ($max > 0) ? array_keys($downloads, $max) : [];

                return [
                    'winners'         => $winners,
                    'support'         => $support,
                    'basedOn'         => 'asset.downloads',
                    'supportTemplate' => 'MauticPageBundle:SubscribedEvents\AbTest:bargraph.html.php',
                ];
            }
        }

        return [
            'winners' => [],
            'support' => [],
            'basedOn' => 'asset.downloads',
        ];
    }
}
