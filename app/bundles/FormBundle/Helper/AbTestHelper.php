<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\EmailBundle\Entity\Email;
use Mautic\PageBundle\Entity\Page;

/**
 * Class AbTestHelper.
 */
class AbTestHelper
{
    /**
     * Determines the winner of A/B test based on number of form submissions.
     *
     * @param MauticFactory $factory
     * @param Page          $parent
     * @param               $children
     *
     * @return array
     */
    public static function determineSubmissionWinner($factory, $parent, $children)
    {
        $repo = $factory->getEntityManager()->getRepository('MauticFormBundle:Submission');

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
            $counts = ($type == 'page') ? $repo->getSubmissionCountsByPage($ids, $startDate) : $repo->getSubmissionCountsByEmail($ids, $startDate);

            $translator = $factory->getTranslator();
            if ($counts) {
                $submissions = $support = $data = [];
                $hasResults  = [];

                $submissionLabel = $translator->trans('mautic.form.abtest.label.submissions');
                $hitLabel        = ($type == 'page') ? $translator->trans('mautic.form.abtest.label.hits') : $translator->trans('mautic.form.abtest.label.sentemils');

                foreach ($counts as $stats) {
                    $submissionRate            = ($stats['total']) ? round(($stats['count'] / $stats['total']) * 100, 2) : 0;
                    $submissions[$stats['id']] = $submissionRate;
                    $data[$submissionLabel][]  = $stats['count'];
                    $data[$hitLabel][]         = $stats['total'];
                    $support['labels'][]       = $stats['id'].':'.$stats['name'].' ('.$submissionRate.'%)';
                    $hasResults[]              = $stats['id'];
                }

                //make sure that parent and published children are included
                if (!in_array($parent->getId(), $hasResults)) {
                    $data[$submissionLabel][] = 0;
                    $data[$hitLabel][]        = 0;
                    $support['labels'][]      = (($type == 'page') ? $parent->getTitle() : $parent->getName()).' (0%)';
                }

                foreach ($children as $c) {
                    if ($c->isPublished()) {
                        if (!in_array($c->getId(), $hasResults)) {
                            $data[$submissionLabel][] = 0;
                            $data[$hitLabel][]        = 0;
                            $support['labels'][]      = (($type == 'page') ? $c->getTitle() : $c->getName()).' (0%)';
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
                asort($submissions);

                //who's the winner?
                $max = max($submissions);

                //get the page ids with the most number of submissions
                $winners = array_keys($submissions, $max);

                return [
                    'winners'         => $winners,
                    'support'         => $support,
                    'basedOn'         => 'form.submissions',
                    'supportTemplate' => 'MauticPageBundle:SubscribedEvents\AbTest:bargraph.html.php',
                ];
            }
        }

        return [
            'winners' => [],
            'support' => [],
            'basedOn' => 'form.submissions',
        ];
    }
}
