<?php

declare(strict_types=1);

namespace Mautic\NotificationBundle\EventListener;

use Mautic\ChannelBundle\Event\ChannelEvent;
use Mautic\ChannelBundle\Model\MessageModel;
use Mautic\NotificationBundle\Entity\Notification;
use Mautic\NotificationBundle\Form\Type\NotificationListType;
use Mautic\NotificationBundle\Helper\OneSignalIntegrationHelper;
use Mautic\ReportBundle\Model\ReportModel;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class ChannelSubscriber
{
    public function __construct(
        private OneSignalIntegrationHelper $oneSignalIntegrationHelper,
    ) {
    }

    #[AsEventListener(
        event: ChannelEvent::class,
        priority: 70
    )]
    public function onAddChannel(ChannelEvent $event): void
    {
        $integration = $this->oneSignalIntegrationHelper->getPublishedIntegration();

        if ($integration) {
            $event->addChannel(
                'notification',
                [
                    MessageModel::CHANNEL_FEATURE => [
                        'campaignAction'             => CampaignSubscriber::EVENT_ACTION_SEND_NOTIFICATION,
                        'campaignDecisionsSupported' => [
                            'page.pagehit',
                            'asset.download',
                            'form.submit',
                        ],
                        'lookupFormType' => NotificationListType::class,
                        'repository'     => Notification::class,
                        'lookupOptions'  => [
                            'mobile'  => false,
                            'desktop' => true,
                        ],
                    ],
                    ReportModel::CHANNEL_FEATURE => [
                        'table' => 'push_notifications',
                    ],
                ]
            );

            $supportedFeatures = $integration->getSupportedFeatures();

            if (in_array('mobile', $supportedFeatures)) {
                $event->addChannel(
                    'mobile_notification',
                    [
                        MessageModel::CHANNEL_FEATURE => [
                            'campaignAction'             => CampaignSubscriber::EVENT_ACTION_SEND_MOBILE_NOTIFICATION,
                            'campaignDecisionsSupported' => [
                                'page.pagehit',
                                'asset.download',
                                'form.submit',
                            ],
                            'lookupFormType'             => NotificationListType::class,
                            'repository'                 => Notification::class,
                            'lookupOptions'              => [
                                'mobile'  => true,
                                'desktop' => false,
                            ],
                        ],
                    ]
                );
            }
        }
    }
}
