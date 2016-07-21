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
use Mautic\AssetBundle\Model\AssetModel;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Factory\MauticFactory;
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
    protected $model;

    /**
     * LeadSubscriber constructor.
     *
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory, AssetModel $model)
    {
        parent::__construct($factory);

        $this->model = $model;
    }

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
        if (!$event->isApplicable($eventTypeKey)) {

            return;
        }

        $lead = $event->getLead();

        /** @var \Mautic\AssetBundle\Entity\DownloadRepository $downloadRepository */
        $downloadRepository = $this->em->getRepository('MauticAssetBundle:Download');
        $downloads          = $downloadRepository->getLeadDownloads($lead->getId(), $event->getQueryOptions());

        // Add total number to counter
        $event->addToCounter($eventTypeKey, $downloads);

        if (!$event->isEngagementCount()) {

            // Add the downloads to the event array
            foreach ($downloads['results'] as $download) {
                $asset = $this->model->getEntity($download['asset_id']);
                $event->addEvent(
                    [
                        'event'           => $eventTypeKey,
                        'eventLabel'      => [
                            'label' => $download['title'],
                            'href'  => $this->router->generate('mautic_asset_action', ['objectAction' => 'view', 'objectId' => $download['asset_id']])
                        ],
                        'extra'           => [
                            'asset'            => $asset,
                            'assetDownloadUrl' => $this->model->generateUrl($asset)
                        ],
                        'eventType'       => $eventTypeName,
                        'timestamp'       => $download['dateDownload'],
                        'icon'            => 'fa-download',
                        'contentTemplate' => 'MauticAssetBundle:SubscribedEvents\Timeline:index.html.php'
                    ]
                );
            }
        }
    }

    /**
     * @param LeadChangeEvent $event
     */
    public function onLeadChange(LeadChangeEvent $event)
    {
        $this->model->getDownloadRepository()->updateLeadByTrackingId(
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
        $this->model->getDownloadRepository()->updateLead($event->getLoser()->getId(), $event->getVictor()->getId());
    }
}
