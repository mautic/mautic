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
<<<<<<< HEAD
    public function __construct(private IntegrationHelper $helper)
=======
    private \Mautic\PluginBundle\Helper\IntegrationHelper $helper;

    public function __construct(IntegrationHelper $helper)
>>>>>>> 11b4805f88 ([type-declarations] Re-run rector rules on plugins, Report, Sms, User, Lead, Dynamic, Config bundles)
    {
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
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
