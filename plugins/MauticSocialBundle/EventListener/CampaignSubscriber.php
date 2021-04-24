<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticSocialBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use MauticPlugin\MauticSocialBundle\Form\Type\TweetSendType;
use MauticPlugin\MauticSocialBundle\Helper\CampaignEventHelper;
use MauticPlugin\MauticSocialBundle\SocialEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Translation\TranslatorInterface;

class CampaignSubscriber implements EventSubscriberInterface
{
    /**
     * @var CampaignEventHelper
     */
    private $campaignEventHelper;

    /**
     * @var IntegrationHelper
     */
    private $integrationHelper;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        CampaignEventHelper $campaignEventHelper,
        IntegrationHelper $integrationHelper,
        TranslatorInterface $translator
    ) {
        $this->campaignEventHelper = $campaignEventHelper;
        $this->integrationHelper   = $integrationHelper;
        $this->translator          = $translator;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD        => ['onCampaignBuild', 0],
            SocialEvents::ON_CAMPAIGN_TRIGGER_ACTION => ['onCampaignAction', 0],
        ];
    }

    public function onCampaignBuild(CampaignBuilderEvent $event)
    {
        $integration = $this->integrationHelper->getIntegrationObject('Twitter');
        if ($integration && $integration->getIntegrationSettings()->isPublished()) {
            $action = [
                'label'           => 'mautic.social.twitter.tweet.event.open',
                'description'     => 'mautic.social.twitter.tweet.event.open_desc',
                'eventName'       => SocialEvents::ON_CAMPAIGN_TRIGGER_ACTION,
                'formTypeOptions' => ['update_select' => 'campaignevent_properties_channelId'],
                'formType'        => TweetSendType::class,
                'channel'         => 'social.tweet',
                'channelIdField'  => 'channelId',
            ];

            $event->addAction('twitter.tweet', $action);
        }
    }

    public function onCampaignAction(CampaignExecutionEvent $event)
    {
        $event->setChannel('social.twitter');
        if ($response = $this->campaignEventHelper->sendTweetAction($event->getLead(), $event->getEvent())) {
            return $event->setResult($response);
        }

        return $event->setFailed(
            $this->translator->trans('mautic.social.twitter.error.handle_not_found')
        );
    }
}
