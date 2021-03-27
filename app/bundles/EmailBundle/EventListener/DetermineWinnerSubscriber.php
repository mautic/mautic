<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Event\DetermineWinnerEvent;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\PageBundle\Entity\Hit;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Translation\TranslatorInterface;

class DetermineWinnerSubscriber implements EventSubscriberInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(EntityManagerInterface $em, TranslatorInterface $translator)
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
            EmailEvents::ON_DETERMINE_OPEN_RATE_WINNER         => ['onDetermineOpenRateWinner', 0],
            EmailEvents::ON_DETERMINE_CLICKTHROUGH_RATE_WINNER => ['onDetermineClickthroughRateWinner', 0],
        ];
    }

    /**
     * Determines the winner of A/B test based on open rate.
     */
    public function onDetermineOpenRateWinner(DetermineWinnerEvent $event)
    {
        $parameters = $event->getParameters();
        $parent     = $parameters['parent'];
        $children   = $parameters['children'];

        /** @var \Mautic\EmailBundle\Entity\StatRepository $repo */
        $repo = $this->em->getRepository(Stat::class);
        /** @var Email $parent */
        $ids       = $parent->getRelatedEntityIds();
        $startDate = $parent->getVariantStartDate();

        if (null != $startDate && !empty($ids)) {
            //get their bounce rates
            $counts = $repo->getOpenedRates($ids, $startDate);

            $translator = $this->translator;

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
                foreach ($support['data'] as $data) {
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

                $event->setAbTestResults([
                    'winners'         => $winners,
                    'support'         => $support,
                    'basedOn'         => 'email.openrate',
                    'supportTemplate' => 'MauticPageBundle:SubscribedEvents\AbTest:bargraph.html.php',
                ]);

                return;
            }
        }

        $event->setAbTestResults([
            'winners' => [],
            'support' => [],
            'basedOn' => 'email.openrate',
        ]);
    }

    /**
     * Determines the winner of A/B test based on clickthrough rates.
     */
    public function onDetermineClickthroughRateWinner(DetermineWinnerEvent $event)
    {
        $parameters = $event->getParameters();
        $parent     = $parameters['parent'];
        $children   = $parameters['children'];

        /** @var \Mautic\PageBundle\Entity\HitRepository $pageRepo */
        $pageRepo = $this->em->getRepository(Hit::class);
        /** @var \Mautic\EmailBundle\Entity\StatRepository $emailRepo */
        $emailRepo = $this->em->getRepository(Stat::class);
        /** @var Email $parent */
        $ids = $parent->getRelatedEntityIds();

        $startDate = $parent->getVariantStartDate();
        if (null != $startDate && !empty($ids)) {
            //get their bounce rates
            $clickthroughCounts = $pageRepo->getEmailClickthroughHitCount($ids, $startDate);
            $sentCounts         = $emailRepo->getSentCounts($ids, $startDate);

            $translator = $this->translator;
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

                    $data[$translator->trans('mautic.email.abtest.label.clickthrough')][]     = $count;
                    $data[$translator->trans('mautic.email.abtest.label.opened')][]           = $sentCounts[$id];
                    $hasResults[]                                                             = $id;
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
                foreach ($support['data'] as $data) {
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

                $event->setAbTestResults([
                    'winners'         => $winners,
                    'support'         => $support,
                    'basedOn'         => 'email.clickthrough',
                    'supportTemplate' => 'MauticPageBundle:SubscribedEvents\AbTest:bargraph.html.php',
                ]);

                return;
            }
        }

        $event->setAbTestResults([
            'winners' => [],
            'support' => [],
            'basedOn' => 'email.clickthrough',
        ]);
    }
}
