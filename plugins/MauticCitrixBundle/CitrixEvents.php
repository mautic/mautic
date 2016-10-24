<?php
/**
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCitrixBundle;

/**
 * Class CitrixEvents.
 *
 * Events available for MauticCitrixBundle
 */
final class CitrixEvents
{
    /**
     * The mautic.on_form_submit_action event is dispatched right before a form is submitted.
     *
     * The event listener receives a Mautic\FormBundle\Event\SubmissionEvent instance.
     *
     * @var string
     */
    const ON_FORM_SUBMIT_ACTION = 'mautic.on_form_submit_action';

    /**
     * The mautic.on_form_validate_action event is dispatched when a form is validated.
     *
     * The event listener receives a Mautic\FormBundle\Event\ValidationEvent instance.
     *
     * @var string
     */
    const ON_FORM_VALIDATE_ACTION = 'mautic.on_form_validate_action';

    /**
     * The mautic.on_citrix_webinar_event event is dispatched when a campaign event is triggered.
     *
     * The event listener receives a Mautic\CampaignBundle\Event\CampaignExecutionEvent instance.
     *
     * @var string
     */
    const ON_CITRIX_WEBINAR_EVENT = 'mautic.on_citrix_webinar_event';

    /**
     * The mautic.on_citrix_meeting_event event is dispatched when a campaign event is triggered.
     *
     * The event listener receives a Mautic\CampaignBundle\Event\CampaignExecutionEvent instance.
     *
     * @var string
     */
    const ON_CITRIX_MEETING_EVENT = 'mautic.on_citrix_meeting_event';

    /**
     * The mautic.on_citrix_training_event event is dispatched when a campaign event is triggered.
     *
     * The event listener receives a Mautic\CampaignBundle\Event\CampaignExecutionEvent instance.
     *
     * @var string
     */
    const ON_CITRIX_TRAINING_EVENT = 'mautic.on_citrix_training_event';

    /**
     * The mautic.on_citrix_assist_event event is dispatched when a campaign event is triggered.
     *
     * The event listener receives a Mautic\CampaignBundle\Event\CampaignExecutionEvent instance.
     *
     * @var string
     */
    const ON_CITRIX_ASSIST_EVENT = 'mautic.on_citrix_assist_event';
    
}
