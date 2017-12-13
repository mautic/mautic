<?php

/*
 * @copyright   2017 Mautic, Inc. All rights reserved
 * @author      WebMecanik
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticWebinarBundle;

/**
 * Class WebinarEvents.
 *
 * Events available for MauticwebinarBundle
 */
final class WebinarEvents
{
    /**
     * The mautic.on_webinar_event event is dispatched when a campaign event is triggered.
     *
     * The event listener receives a Mautic\CampaignBundle\Event\CampaignExecutionEvent instance.
     *
     * @var string
     */
    const ON_WEBINAR_EVENT                = 'mautic.on_webinar_event';
    const ON_CAMPAIGN_TRIGGER_ACTION      = 'mautic.on_campaign_trigger_action';
    const ON_FORM_SUBMIT_ACTION_TRIGGERED = 'mautic.onFormSubmitActionTriggered';
}
