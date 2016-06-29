<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\AssetBundle\EventListener;

use Mautic\AssetBundle\AssetEvents;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\LeadBundle\Event\LeadChangeEvent;
use Mautic\LeadBundle\Event\LeadMergeEvent;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\LeadEvents;

/**
 * Class AssetBundle
 *
 * @package Mautic\AssetBundle\EventListener
 */
class LeadSubscriber extends CommonSubscriber
{
    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return [
            LeadEvents::TIMELINE_ON_GENERATE => ['onTimelineGenerate', 0],
            LeadEvents::CURRENT_LEAD_CHANGED => ['onLeadChange', 0],
            LeadEvents::LEAD_POST_MERGE      => ['onLeadMerge', 0],
        ];
    }

    /**
     * Compile events for the lead timeline
     *
     * @param LeadTimelineEvent $event
     */
    public function onTimelineGenerate(LeadTimelineEvent $event)
    {
        // Set available event types
        $eventTypeKey  = 'asset.download';
        $eventTypeName = $this->translator->trans('mautic.asset.event.download');
        $event->addEventType($eventTypeKey, $eventTypeName);

        // Decide if those events are filtered
        $filters = $event->getEventFilters();

        if (!$event->isApplicable($eventTypeKey)) {
            return;
        }

        $lead    = $event->getLead();
        $options = ['ipIds' => [], 'filters' => $filters];


        /** @var \Mautic\CoreBundle\Entity\IpAddress $ip */
        /*
        foreach ($lead->getIpAddresses() as $ip) {
            $options['ipIds'][] = $ip->getId();
        }
        */
        /** @var \Mautic\AssetBundle\Entity\DownloadRepository $downloadRepository */
        $downloadRepository = $this->factory->getEntityManager()->getRepository('MauticAssetBundle:Download');

        $downloads = $downloadRepository->getLeadDownloads($lead->getId(), $options);

        /** @var \Mautic\AssetBundle\Model\AssetModel $model */
        $model = $this->factory->getModel('asset.asset');

        // Add the downloads to the event array
        foreach ($downloads as $download) {
            $event->addEvent(
                [
                    'event'           => $eventTypeKey,
                    'eventLabel'      => $eventTypeName,
                    'timestamp'       => $download['dateDownload'],
                    'extra'           => [
                        'asset' => $model->getEntity($download['asset_id'])
                    ],
                    'contentTemplate' => 'MauticAssetBundle:SubscribedEvents\Timeline:index.html.php',
                    'icon'            => 'fa-download'
                ]
            );
        }
    }

    /**
     * @param LeadChangeEvent $event
     */
    public function onLeadChange(LeadChangeEvent $event)
    {
        $this->factory->getModel('asset')->getDownloadRepository()->updateLeadByTrackingId(
            $event->getNewLead()->getId(),
            $event->getNewTrackingId(),
            $event->getOldTrackingId()
        );
    }

    /**
     * @param LeadMergeEvent $event
     */
    public function onLeadMerge(LeadMergeEvent $event)
    {
        $this->factory->getModel('asset')->getDownloadRepository()->updateLead($event->getLoser()->getId(), $event->getVictor()->getId());
    }
}
