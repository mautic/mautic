<?php

declare(strict_types=1);

namespace MauticPlugin\MauticSocialBundle\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Mautic\ChannelBundle\Event\ChannelEvent;
use Mautic\ChannelBundle\Model\MessageModel;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use MauticPlugin\MauticSocialBundle\Form\Type\TweetListType;

final readonly class ChannelSubscriber
{
    public function __construct(
        private IntegrationHelper $helper,
    ) {
    }

    #[AsEventListener(event: ChannelEvent::class, priority: 80)]
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
