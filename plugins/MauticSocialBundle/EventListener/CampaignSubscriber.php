<?php

namespace MauticPlugin\MauticSocialBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\PendingEvent;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use MauticPlugin\MauticSocialBundle\Form\Type\TweetSendType;
use MauticPlugin\MauticSocialBundle\Helper\CampaignEventHelper;
use MauticPlugin\MauticSocialBundle\SocialEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class CampaignSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private CampaignEventHelper $campaignEventHelper,
        private IntegrationHelper $integrationHelper,
        private TranslatorInterface $translator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD      => ['onCampaignBuild', 0],
            SocialEvents::ON_CAMPAIGN_BATCH_ACTION => ['onCampaignAction', 0],
        ];
    }

    public function onCampaignBuild(CampaignBuilderEvent $event): void
    {
        $integration = $this->integrationHelper->getIntegrationObject('Twitter');
        if ($integration && $integration->getIntegrationSettings()->isPublished()) {
            $action = [
                'label'           => 'mautic.social.twitter.tweet.event.open',
                'description'     => 'mautic.social.twitter.tweet.event.open_desc',
                'batchEventName'  => SocialEvents::ON_CAMPAIGN_BATCH_ACTION,
                'formTypeOptions' => ['update_select' => 'campaignevent_properties_channelId'],
                'formType'        => TweetSendType::class,
                'channel'         => 'social.tweet',
                'channelIdField'  => 'channelId',
            ];

            $event->addAction('twitter.tweet', $action);
        }
    }

    public function onCampaignAction(PendingEvent $event): void
    {
        $event->setChannel('social.twitter');
        $campaignEvent = $event->getEvent();

        foreach ($event->getPending() as $log) {
            $response = $this->campaignEventHelper->sendTweetAction($log->getLead(), $campaignEvent);

            if (false === $response) {
                $event->passWithError($log, $this->translator->trans('mautic.social.twitter.error.handle_not_found'));

                continue;
            }

            $log->appendToMetadata($response);

            if (!empty($response['failed']) && isset($response['reason'])) {
                $event->passWithError($log, $response['reason']);

                continue;
            }

            $event->pass($log);
        }
    }
}
