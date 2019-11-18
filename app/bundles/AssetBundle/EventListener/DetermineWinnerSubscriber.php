<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Mautic\AssetBundle\AssetEvents;
use Mautic\CoreBundle\Event\DetermineWinnerEvent;
use Mautic\EmailBundle\Entity\Email;
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
            AssetEvents::ON_DETERMINE_DOWNLOAD_RATE_WINNER => ['onDetermineDownloadRateWinner', 0],
        ];
    }

    /**
     * Determines the winner of A/B test based on number of asset downloads.
     *
     * @param DetermineWinnerEvent $event
     */
    public function onDetermineDownloadRateWinner(DetermineWinnerEvent $event)
    {
        $repo     = $this->em->getRepository('MauticAssetBundle:Download');
        $parent   = $event->getParameters()['parent'];
        $children = $event->getParameters()['children'];

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

            $translator = $this->translator;
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

                $event->setAbTestResults([
                    'winners'         => $winners,
                    'support'         => $support,
                    'basedOn'         => 'asset.downloads',
                    'supportTemplate' => 'MauticPageBundle:SubscribedEvents\AbTest:bargraph.html.php',
                ]);

                return;
            }
        }

        $event->setAbTestResults([
            'winners' => [],
            'support' => [],
            'basedOn' => 'asset.downloads',
        ]);
    }
}
