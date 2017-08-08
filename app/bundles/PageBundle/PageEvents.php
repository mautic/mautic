<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle;

/**
 * Class PageEvents.
 *
 * Events available for PageBundle
 */
final class PageEvents
{
    /**
     * The mautic.video_on_hit event is thrown when a public page is browsed and a hit recorded in the analytics table.
     *
     * The event listener receives a Mautic\PageBundle\Event\VideoHitEvent instance.
     *
     * @var string
     */
    const VIDEO_ON_HIT = 'mautic.video_on_hit';

    /**
 * The mautic.page_on_hit event is thrown when a public page is browsed and a hit recorded in the analytics table.
 *
 * The event listener receives a Mautic\PageBundle\Event\PageHitEvent instance.
 *
 * @var string
 */
    const PAGE_ON_HIT = 'mautic.page_on_hit';

    /**
 * The mautic.track_on_hit event is thrown when a public page is browsed and a 3rd party tracking pixel is available
 *
 * The event listener receives a Mautic\PageBundle\Event\TrackHitEvent instance.
 *
 * @var string
 */
    const TRACK_ON_HIT = 'mautic.track_on_hit';

    /**
     * The mautic.page_on_build event is thrown before displaying the page builder form to allow adding of tokens.
     *
     * The event listener receives a Mautic\PageBundle\Event\PageEvent instance.
     *
     * @var string
     */
    const PAGE_ON_BUILD = 'mautic.page_on_build';

    /**
     * The mautic.page_on_display event is thrown before displaying the page content.
     *
     * The event listener receives a Mautic\PageBundle\Event\PageDisplayEvent instance.
     *
     * @var string
     */
    const PAGE_ON_DISPLAY = 'mautic.page_on_display';

    /**
     * The mautic.page_pre_save event is thrown right before a page is persisted.
     *
     * The event listener receives a Mautic\PageBundle\Event\PageEvent instance.
     *
     * @var string
     */
    const PAGE_PRE_SAVE = 'mautic.page_pre_save';

    /**
     * The mautic.page_post_save event is thrown right after a page is persisted.
     *
     * The event listener receives a Mautic\PageBundle\Event\PageEvent instance.
     *
     * @var string
     */
    const PAGE_POST_SAVE = 'mautic.page_post_save';

    /**
     * The mautic.page_pre_delete event is thrown prior to when a page is deleted.
     *
     * The event listener receives a Mautic\PageBundle\Event\PageEvent instance.
     *
     * @var string
     */
    const PAGE_PRE_DELETE = 'mautic.page_pre_delete';

    /**
     * The mautic.page_post_delete event is thrown after a page is deleted.
     *
     * The event listener receives a Mautic\PageBundle\Event\PageEvent instance.
     *
     * @var string
     */
    const PAGE_POST_DELETE = 'mautic.page_post_delete';

    /**
     * The mautic.redirect_do_not_track event is thrown when converting email links to trackables/redirectables in order to compile of list of tokens/URLs
     * to ignore.
     *
     * The event listener receives a Mautic\PageBundle\Event\UntrackableUrlsEvent instance.
     *
     * @var string
     */
    const REDIRECT_DO_NOT_TRACK = 'mautic.redirect_do_not_track';

    /**
     * The mautic.page.on_campaign_trigger_decision event is fired when the campaign decision triggers.
     *
     * The event listener receives a
     * Mautic\CampaignBundle\Event\CampaignExecutionEvent
     *
     * @var string
     */
    const ON_CAMPAIGN_TRIGGER_DECISION = 'mautic.page.on_campaign_trigger_decision';

    /**
     * The mautic.page.on_campaign_trigger_action event is fired when the campaign action triggers.
     *
     * The event listener receives a
     * Mautic\CampaignBundle\Event\CampaignExecutionEvent
     *
     * @var string
     */
    const ON_CAMPAIGN_TRIGGER_ACTION = 'mautic.page.on_campaign_trigger_action';
}
