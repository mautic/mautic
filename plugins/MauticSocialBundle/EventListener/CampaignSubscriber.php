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
use Mautic\CoreBundle\Helper\CookieHelper;
use MauticPlugin\MauticSocialBundle\Helper\CampaignEventHelper;
use MauticPlugin\MauticSocialBundle\SocialEvents;

class CampaignSubscriber extends CommonSubscriber
{
    /**
     * @var CampaignEventHelper
     */
    protected $helper;

    /**
     * @var CookieHelper
     */
    protected $cookieHelper;

    /**
     * CampaignSubscriber constructor.
     *
     * @param MauticFactory       $factory
     * @param CampaignEventHelper $helper
     * @param CookieHelper        $cookieHelper
     */
    public function __construct(MauticFactory $factory, CampaignEventHelper $helper, CookieHelper $cookieHelper)
    {
        $this->helper       = $helper;
        $this->cookieHelper = $cookieHelper;
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
            'label'           => 'mautic.social.twitter.tweet.event.open',
            'description'     => 'mautic.social.twitter.tweet.event.open_desc',
            'eventName'       => SocialEvents::ON_CAMPAIGN_TRIGGER_ACTION,
            'formTypeOptions' => ['update_select' => 'campaignevent_properties_channelId'],
            'formType'        => 'tweetsend_list',
            'channel'         => 'social.tweet',
            'channelIdField'  => 'channelId',
        ];

        $event->addAction('twitter.tweet', $action);

        $action = [
            'label'                  => 'mautic.social.facebook.pixel.event.send',
            'description'            => 'mautic.social.facebook.pixel.event.send_desc',
            'eventName'              => SocialEvents::ON_CAMPAIGN_TRIGGER_ACTION,
            'formType'               => 'facebook_pixel_send_action',
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

        $event->addAction('facebook.pixel.send', $action);
    }

    /**
     * @param CampaignExecutionEvent $event
     */
    public function onCampaignAction(CampaignExecutionEvent $event)
    {
        if ($event->checkContext('')) {
            $event->setChannel('twitter.tweet');
            if ($response = $this->helper->sendTweetAction($event->getLead(), $event->getEvent())) {
                return $event->setResult($response);
            }

            return $event->setFailed(
                $this->translator->trans('mautic.social.twitter.error.handle_not_found')
            );
        } elseif ($event->checkContext('facebook.pixel.send')) {
            $lead        = $event->getLead();
            $sessionName = 'mtc-fb-event-'.$lead->getId();
            $this->cookieHelper->setCookie($sessionName, '|'.$event->getConfig()['action'].':'.$event->getConfig()['label']);

            return $event->setResult(true);
        }
    }
}
