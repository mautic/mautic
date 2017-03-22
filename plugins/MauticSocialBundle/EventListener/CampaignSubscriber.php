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
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Factory\MauticFactory;
use MauticPlugin\MauticSocialBundle\Helper\CampaignEventHelper;
use MauticPlugin\MauticSocialBundle\SocialEvents;

class CampaignSubscriber extends CommonSubscriber
{
    /**
     * @var CampaignEventHelper
     */
    protected $helper;

    /**
     * CampaignSubscriber constructor.
     *
     * @param MauticFactory       $factory
     * @param CampaignEventHelper $helper
     */
    public function __construct(MauticFactory $factory, CampaignEventHelper $helper)
    {
        $this->helper = $helper;

        parent::__construct($factory);
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

    /**
     * @param CampaignBuilderEvent $event
     */
    public function onCampaignBuild(CampaignBuilderEvent $event)
    {
        $action = [
            'label'       => 'mautic.social.twitter.tweet.event.open',
            'description' => 'mautic.social.twitter.tweet.event.open_desc',
            'eventName'   => SocialEvents::ON_CAMPAIGN_TRIGGER_ACTION,
            'formType'    => 'twitter_tweet',
            'formTheme'   => 'MauticSocialBundle:FormTheme',
            'channel'     => 'social.tweet',
        ];

        $event->addAction('twitter.tweet', $action);
    }

    /**
     * @param CampaignExecutionEvent $event
     */
    public function onCampaignAction(CampaignExecutionEvent $event)
    {
        $event->setChannel('social.twitter');
        if ($response = $this->helper->sendTweetAction($event->getLead(), $event->getEvent())) {
            return $event->setResult($response);
        }

        return $event->setFailed(
            $this->translator->trans('mautic.social.twitter.error.handle_not_found')
        );
    }
}
