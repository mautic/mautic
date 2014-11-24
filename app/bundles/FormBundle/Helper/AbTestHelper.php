<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\PageBundle\Entity\Page;

/**
 * Class AbTestHelper
 */
class AbTestHelper
{

    /**
     * Determines the winner of A/B test based on number of form submissions
     *
     * @param MauticFactory $factory
     * @param Page          $parent
     * @param               $children
     *
     * @return array
     */
    public static function determineSubmissionWinner ($factory, $parent, $children)
    {
        $repo = $factory->getEntityManager()->getRepository('MauticFormBundle:Submission');

        $pageIds = array($parent->getId());

        foreach ($children as $c) {
            if ($c->isPublished()) {
                $id        = $c->getId();
                $pageIds[] = $id;
            }
        }

        $startDate = $parent->getVariantStartDate();
        if ($startDate != null && !empty($pageIds)) {
            $counts = $repo->getSubmissionCountsByPage($pageIds, $startDate);

            $translator = $factory->getTranslator();
            if ($counts) {
                $submissions = $support = $data = array();
                $hasResults  = array();
                foreach ($counts as $stats) {
                    $submissionRate                 = ($stats['variant_hits']) ? round(($stats['submissions'] / $stats['variant_hits']) * 100, 2) : 0;
                    $submissions[$stats['page_id']] = $submissionRate;
                    $data[$translator->trans('mautic.form.abtest.label.submissions')][] = $stats['submissions'];
                    $data[$translator->trans('mautic.form.abtest.label.hits')][]        = $stats['variant_hits'];
                    $data[$translator->trans('mautic.form.abtest.label.rates')][]       = $submissionRate;
                    $support['labels'][] = $stats['page_id'] . ':' . $stats['title'];
                    $hasResults[] = $stats['page_id'];
                }

                //make sure that parent and published children are included
                if (!in_array($parent->getId(), $hasResults)) {
                    $data[$translator->trans('mautic.form.abtest.label.submissions')][] = 0;
                    $data[$translator->trans('mautic.form.abtest.label.hits')][]        = 0;
                    $data[$translator->trans('mautic.form.abtest.label.rates')][]       = 0;
                    $support['labels'][] = $parent->getId() . ':' . $parent->getTitle();;
                }

                foreach ($children as $c) {
                    if ($c->isPublished()) {
                        if (!in_array($c->getId(), $hasResults)) {
                            $data[$translator->trans('mautic.form.abtest.label.submissions')][] = 0;
                            $data[$translator->trans('mautic.form.abtest.label.hits')][]        = 0;
                            $data[$translator->trans('mautic.form.abtest.label.rates')][]       = 0;
                            $support['labels'][]                                                = $c->getId() . ':' . $c->getTitle();;
                        }
                    }
                }
                $support['data'] = $data;

                //set max for scales
                $maxes = array();
                foreach ( $support['data'] as $label => $data) {
                    $maxes[] = max($data);
                }
                $top = max($maxes);
                $support['step_width'] = (floor($top / 10) * 10) / 10;

                //put in order from least to greatest just because
                asort($submissions);

                //who's the winner?
                $max = max($submissions);

                //get the page ids with the most number of submissions
                $winners = array_keys($submissions, $max);

                return array(
                    'winners'         => $winners,
                    'support'         => $support,
                    'basedOn'         => 'form.submissions',
                    'supportTemplate' => 'MauticPageBundle:SubscribedEvents\AbTest:bargraph.html.php'
                );
            }
        }

        return array(
            'winners' => array(),
            'support' => array(),
            'basedOn' => 'form.submissions'
        );
    }
}