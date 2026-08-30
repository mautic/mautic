<?php

namespace Mautic\AssetBundle\EventListener;

use Mautic\AssetBundle\AssetEvents;
use Mautic\AssetBundle\Entity\DownloadRepository;
use Mautic\CoreBundle\Event\DetermineWinnerEvent;
use Mautic\EmailBundle\Entity\Email;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class DetermineWinnerSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private DownloadRepository $downloadRepository,
        private TranslatorInterface $translator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AssetEvents::ON_DETERMINE_DOWNLOAD_RATE_WINNER => ['onDetermineDownloadRateWinner', 0],
        ];
    }

    /**
     * Determines the winner of A/B test based on number of asset downloads.
     */
    public function onDetermineDownloadRateWinner(DetermineWinnerEvent $event): void
    {
        $parameters = $event->getParameters();
        $parent     = $parameters['parent'];
        $children   = $parameters['children'];

        // if this is an email A/B test, then link email to page to form submission
        // if it is a page A/B test, then link form submission to page
        $type = ($parent instanceof Email) ? 'email' : 'page';

        $ids = [$parent->getId()];

        foreach ($children as $c) {
            if ($c->isPublished()) {
                $id    = $c->getId();
                $ids[] = $id;
            }
        }

        $startDate = $parent->getVariantStartDate();
        if (null != $startDate) {
            $counts = ('page' === $type) ? $this->downloadRepository->getDownloadCountsByPage($ids, $startDate) : $this->downloadRepository->getDownloadCountsByEmail($ids, $startDate, $parent->getVariantEndDate());

            if ($counts !== []) {
                $downloads  = $support  = $data  = [];
                $hasResults = [];

                $downloadsLabel = $this->translator->trans('mautic.asset.abtest.label.downloads');
                $hitsLabel      = ('page' === $type) ? $this->translator->trans('mautic.asset.abtest.label.hits') : $this->translator->trans('mautic.asset.abtest.label.sentemils');
                foreach ($counts as $stats) {
                    $rate                    = ($stats['total']) ? round(($stats['count'] / $stats['total']) * 100, 2) : 0;
                    $downloads[$stats['id']] = $rate;
                    $data[$downloadsLabel][] = $stats['count'];
                    $data[$hitsLabel][]      = $stats['total'];
                    $support['labels'][]     = $stats['id'].':'.$stats['name'].' ('.$rate.'%)';
                    $hasResults[]            = $stats['id'];
                }

                // make sure that parent and published children are included
                if (!in_array($parent->getId(), $hasResults)) {
                    $data[$downloadsLabel][] = 0;
                    $data[$hitsLabel][]      = 0;
                    $support['labels'][]     = $parent->getId().':'.(('page' === $type) ? $parent->getTitle() : $parent->getName()).' (0%)';
                }

                foreach ($children as $c) {
                    if ($c->isPublished()) {
                        if (!in_array($c->getId(), $hasResults)) {
                            $data[$downloadsLabel][] = 0;
                            $data[$hitsLabel][]      = 0;
                            $support['labels'][]     = $c->getId().':'.(('page' === $type) ? $c->getTitle() : $c->getName()).' (0%)';
                        }
                    }
                }
                $support['data'] = $data;

                // set max for scales
                $maxes = [];
                foreach ($support['data'] as $data) {
                    $maxes[] = max($data);
                }
                $top                   = max($maxes);
                $support['step_width'] = (ceil($top / 10) * 10);

                // put in order from least to greatest just because
                asort($downloads);

                // who's the winner?
                $max = max($downloads);

                // get the page ids with the most number of downloads
                $winners = ($max > 0) ? array_keys($downloads, $max) : [];

                $event->setAbTestResults([
                    'winners'         => $winners,
                    'support'         => $support,
                    'basedOn'         => 'asset.downloads',
                    'supportTemplate' => '@MauticPage/SubscribedEvents/AbTest/bargraph.html.twig',
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
