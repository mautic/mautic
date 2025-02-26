<?php

namespace MauticPlugin\MauticSocialBundle\EventListener;

use Mautic\ChannelBundle\ChannelEvents;
use Mautic\ChannelBundle\Event\ChannelEvent;
use Mautic\ChannelBundle\Model\MessageModel;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use MauticPlugin\MauticSocialBundle\Form\Type\TweetListType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ChannelSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private IntegrationHelper $helper,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ChannelEvents::ADD_CHANNEL => ['onAddChannel', 80],
        ];
    }

    public function onAddChannel(ChannelEvent $event): void
    {
        $integration = $this->helper->getIntegrationObject('Twitter');
        if ($integration && $integration->getIntegrationSettings()->isPublished()) {
            $event->addChannel(
                'tweet',
                [
                    MessageModel::CHANNEL_FEATURE => [
                        'campaignAction'             => 'twitter.tweet',
                        'campaignDecisionsSupported' => [
                            'page.pagehit',
                            'asset.download',
                            'form.submit',
                        ],
                        'lookupFormType' => TweetListType::class,
                        'repository'     => 'MauticSocialBundle:Tweet',
                    ],
                ]
            );
        }
    }
}
