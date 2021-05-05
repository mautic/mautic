<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticSocialBundle;

/**
 * Class SocialEvents.
 *
 * Events available for MauticSocialBundle
 */
final class SocialEvents
{
    /**
     * The mautic.monitor_pre_save event is dispatched right before a monitor is persisted.
     *
     * The event listener receives a
     * MauticPlugin\MauticSocialBundle\Event\SocialEvent instance.
     *
     * @var string
     */
    const MONITOR_PRE_SAVE = 'mautic.monitor_pre_save';

    /**
     * The mautic.monitor_post_save event is dispatched right after a monitor is persisted.
     *
     * The event listener receives a
     * MauticPlugin\MauticSocialBundle\Event\SocialEvent instance.
     *
     * @var string
     */
    const MONITOR_POST_SAVE = 'mautic.monitor_post_save';

    /**
     * The mautic.monitor_pre_delete event is dispatched before a monitor item is deleted.
     *
     * The event listener receives a
     * MauticPlugin\MauticSocialBundle\Event\SocialEvent instance.
     *
     * @var string
     */
    const MONITOR_PRE_DELETE = 'mautic.monitor_pre_delete';

    /**
     * The mautic.monitor_post_delete event is dispatched after a monitor is deleted.
     *
     * The event listener receives a
     * MauticPlugin\MauticSocialBundle\Event\SocialEvent instance.
     *
     * @var string
     */
    const MONITOR_POST_DELETE = 'mautic.monitor_post_delete';

    /**
     * The mautic.monitor_post_process event is dispatched after a monitor is processed passing along the data gleaned.
     *
     * The event listener receives a
     * MauticPlugin\MauticSocialBundle\Event\SocialEvent instance.
     *
     * @var string
     */
    const MONITOR_POST_PROCESS = 'mautic.monitor_post_process';

    /**
     * The mautic.tweet_pre_save event is dispatched right before a tweet is persisted.
     *
     * The event listener receives a
     * MauticPlugin\MauticSocialBundle\Event\SocialEvent instance.
     *
     * @var string
     */
    const TWEET_PRE_SAVE = 'mautic.tweet_pre_save';

    /**
     * The mautic.tweet_post_save event is dispatched right after a tweet is persisted.
     *
     * The event listener receives a
     * MauticPlugin\MauticSocialBundle\Event\SocialEvent instance.
     *
     * @var string
     */
    const TWEET_POST_SAVE = 'mautic.tweet_post_save';

    /**
     * The mautic.tweet_pre_delete event is dispatched before a tweet item is deleted.
     *
     * The event listener receives a
     * MauticPlugin\MauticSocialBundle\Event\SocialEvent instance.
     *
     * @var string
     */
    const TWEET_PRE_DELETE = 'mautic.tweet_pre_delete';

    /**
     * The mautic.tweet_post_delete event is dispatched after a tweet is deleted.
     *
     * The event listener receives a
     * MauticPlugin\MauticSocialBundle\Event\SocialEvent instance.
     *
     * @var string
     */
    const TWEET_POST_DELETE = 'mautic.tweet_post_delete';

    /**
     * The mautic.social.on_campaign_trigger_action event is fired when the campaign action triggers.
     *
     * The event listener receives a
     * Mautic\CampaignBundle\Event\CampaignExecutionEvent
     *
     * @var string
     */
    const ON_CAMPAIGN_TRIGGER_ACTION = 'mautic.social.on_campaign_trigger_action';
}
