<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 * @link        https://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticSocialBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;

class CampaignSubscriber extends CommonSubscriber
{
    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            CampaignEvents::CAMPAIGN_ON_BUILD => array('onCampaignBuild', 0),
            //EmailEvents::EMAIL_ON_SEND        => array('onEmailSend', 0),
            //EmailEvents::EMAIL_ON_OPEN        => array('onEmailOpen', 0)
        );
    }

    /*
     * @param CampaignBuilderEvent $event
     */
    public function onCampaignBuild(CampaignBuilderEvent $event)
    {
        $action = array(
            'label'           => 'mautic.social.twitter.tweet.event.open',
            'description'     => 'mautic.social.twitter.tweet.event.open_desc',
            'callback'        => 'MauticPlugin\MauticSocialBundle\Helper\CampaignEventHelper::sendTweetAction',
            'formType'        => 'twitter_tweet',
            'formTheme'       => 'MauticSocialBundle:FormTheme\Campaigns'
        );

        $event->addAction('twitter.tweet', $action);
    }
}