<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Event\DetermineWinnerEvent;
use Mautic\PageBundle\PageEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class WinnerDeterminerSubscriber.
 */
class DetermineWinnerSubscriber implements EventSubscriberInterface
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param EntityManager       $em
     * @param TranslatorInterface $translator
     */
    public function __construct(EntityManager $em, TranslatorInterface $translator)
    {
        $this->em         = $em;
        $this->translator = $translator;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            PageEvents::ON_DETERMINE_BOUNCE_RATE_WINNER => ['onDetermineBounceRateWinner', 0],
            PageEvents::ON_DETERMINE_DWELL_TIME_WINNER  => ['onDetermineDwellTimeWinner', 0],
        ];
    }

    /**
     * Determines the winner of A/B test based on bounce rates.
     *
     * @param DetermineWinnerEvent $event
     */
    public function onDetermineBounceRateWinner(DetermineWinnerEvent $event)
    {
        //find the hits that did not go any further
        $repo      = $this->em->getRepository('MauticPageBundle:Hit');
        $parent    = $event->getParameters()['parent'];
        $children  = $event->getParameters()['children'];
        $pageIds   = $parent->getRelatedEntityIds();
        $startDate = $parent->getVariantStartDate();

        if ($startDate != null && !empty($pageIds)) {
            //get their bounce rates
            $counts = $repo->getBounces($pageIds, $startDate, true);
            if ($counts) {
                // Group by translation
                $combined = [
                    $parent->getId() => $counts[$parent->getId()],
                ];

                if ($parent->hasTranslations()) {
                    $translations = $parent->getTranslationChildren()->getKeys();

                    foreach ($translations as $translation) {
                        $combined[$parent->getId()]['bounces'] += $counts[$translation]['bounces'];
                        $combined[$parent->getId()]['totalHits'] += $counts[$translation]['totalHits'];
                        $combined[$parent->getId()]['rate'] = ($counts[$parent->getId()]['totalHits']) ? round(
                            ($counts[$parent->getId()]['bounces'] / $counts[$parent->getId()]['totalHits']) * 100,
                            2
                        ) : 0;
                    }
                }

                foreach ($children as $child) {
                    if ($child->hasTranslations()) {
                        $combined[$child->getId()] = $counts[$child->getId()];
                        $translations              = $child->getTranslationChildren()->getKeys();
                        foreach ($translations as $translation) {
                            $combined[$child->getId()]['bounces'] += $counts[$translation]['bounces'];
                            $combined[$child->getId()]['totalHits'] += $counts[$translation]['totalHits'];
                            $combined[$child->getId()]['rate'] = ($counts[$child->getId()]['totalHits']) ? round(
                                ($counts[$child->getId()]['bounces'] / $counts[$child->getId()]['totalHits']) * 100,
                                2
                            ) : 0;
                        }
                    }
                }
                unset($counts);

                //let's arrange by rate
                $rates             = [];
                $support['data']   = [];
                $support['labels'] = [];
                $bounceLabel       = $factory->getTranslator()->trans('mautic.page.abtest.label.bounces');

                foreach ($combined as $pid => $stats) {
                    $rates[$pid]                     = $stats['rate'];
                    $support['data'][$bounceLabel][] = $rates[$pid];
                    $support['labels'][]             = $pid.':'.$stats['title'];
                }

                $max                   = max($rates);
                $support['step_width'] = (ceil($max / 10) * 10);

                //get the page ids with the greatest average dwell time
                $winners = ($max > 0) ? array_keys($rates, $max) : [];

                $event->setAbTestResults([
                    'winners'         => $winners,
                    'support'         => $support,
                    'basedOn'         => 'page.bouncerate',
                    'supportTemplate' => 'MauticPageBundle:SubscribedEvents\AbTest:bargraph.html.php',
                ]);

                return;
            }
        }

        $event->setAbTestResults([
            'winners' => [],
            'support' => [],
            'basedOn' => 'page.bouncerate',
        ]);
    }

    /**
     * Determines the winner of A/B test based on dwell time rates.
     *
     * @param DetermineWinnerEvent $event
     */
    public function onDetermineDwellTimeWinner(DetermineWinnerEvent $event)
    {
        //find the hits that did not go any further
        $repo      = $this->em->getEntityManager()->getRepository('MauticPageBundle:Hit');
        $parent    = $event->getParameters()['parent'];
        $pageIds   = $parent->getRelatedEntityIds();
        $startDate = $parent->getVariantStartDate();

        if ($startDate != null && !empty($pageIds)) {
            //get their bounce rates
            $counts     = $repo->getDwellTimesForPages($pageIds, ['fromDate' => $startDate]);
            $translator = $factory->getTranslator();
            $support    = [];

            if ($counts) {
                //in order to get a fair grade, we have to compare the averages here since a page that is only shown
                //25% of the time will have a significantly lower sum than a page shown 75% of the time
                $avgs              = [];
                $support['data']   = [];
                $support['labels'] = [];
                foreach ($counts as $pid => $stats) {
                    $avgs[$pid]                                                                          = $stats['average'];
                    $support['data'][$translator->trans('mautic.page.abtest.label.dewlltime.average')][] = $stats['average'];
                    $support['labels'][]                                                                 = $pid.':'.$stats['title'];
                }

                //set max for scales
                $max                   = max($avgs);
                $support['step_width'] = (ceil($max / 10) * 10);

                //get the page ids with the greatest average dwell time
                $winners = ($max > 0) ? array_keys($avgs, $max) : [];

                $event->setAbTestResults([
                    'winners'         => $winners,
                    'support'         => $support,
                    'basedOn'         => 'page.dwelltime',
                    'supportTemplate' => 'MauticPageBundle:SubscribedEvents\AbTest:bargraph.html.php',
                ]);
            }
        }

        $event->setAbTestResults([
            'winners' => [],
            'support' => [],
            'basedOn' => 'page.dwelltime',
        ]);
    }
}
