<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFocusBundle\EventListener;

use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\PendingEvent;
use Mautic\PageBundle\Helper\TrackingHelper;
use MauticPlugin\MauticFocusBundle\FocusEvents;
use MauticPlugin\MauticFocusBundle\Form\Type\FocusShowType;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

final readonly class CampaignSubscriber
{
    public function __construct(
        private TrackingHelper $trackingHelper,
        private RouterInterface $router,
    ) {
    }

    #[AsEventListener(
        event: CampaignBuilderEvent::class,
        priority: 0
    )]
    public function onCampaignBuild(CampaignBuilderEvent $event): void
    {
        $action = [
            'label'                  => 'mautic.focus.campaign.event.show_focus',
            'description'            => 'mautic.focus.campaign.event.show_focus_descr',
            'batchEventName'         => FocusEvents::ON_CAMPAIGN_BATCH_ACTION,
            'formType'               => FocusShowType::class,
            'formTheme'              => '@MauticFocus/FormTheme/FocusShowList/focusshow_list_row.html.twig',
            'formTypeOptions'        => ['update_select' => 'campaignevent_properties_focus'],
            'connectionRestrictions' => [
                'anchor' => [
                    'decision.inaction',
                ],
                'source' => [
                    'decision' => [
                        'page.pagehit',
                    ],
                ],
            ],
        ];
        $event->addAction('focus.show', $action);
    }

    #[AsEventListener(
        event: FocusEvents::ON_CAMPAIGN_BATCH_ACTION,
        priority: 0
    )]
    public function onCampaignTriggerAction(PendingEvent $event): void
    {
        $focusId = (int) $event->getEvent()->getProperties()['focus'];

        foreach ($event->getPending() as $log) {
            if (!$focusId) {
                $event->fail($log, 'Focus is not configured.');

                continue;
            }

            $values                 = [];
            $values['focus_item'][] = ['id' => $focusId, 'js' => $this->router->generate('mautic_focus_generate', ['id' => $focusId], UrlGeneratorInterface::ABSOLUTE_URL)];
            $this->trackingHelper->updateCacheItem($values);

            $event->pass($log);
        }
    }
}
