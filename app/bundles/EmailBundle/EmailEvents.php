<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle;

/**
 * Class EmailEvents
 * Events available for EmailBundle
 *
 * @package Mautic\EmailBundle
 */
final class EmailEvents
{

    /**
     * The mautic.email_on_open event is thrown when an email is opened
     *
     * The event listener receives a
     * Mautic\EmailBundle\Event\EmailOpenEvent instance.
     *
     * @var string
     */
    const EMAIL_ON_OPEN  = 'mautic.email_on_open';

    /**
     * The mautic.email_on_send event is thrown when an email is sent
     *
     * The event listener receives a
     * Mautic\EmailBundle\Event\EmailSendEvent instance.
     *
     * @var string
     */
    const EMAIL_ON_SEND  = 'mautic.email_on_send';


    /**
     * The mautic.email_on_display event is thrown when an email is viewed via a browser
     *
     * The event listener receives a
     * Mautic\EmailBundle\Event\EmailSendEvent instance.
     *
     * @var string
     */
    const EMAIL_ON_DISPLAY  = 'mautic.email_on_display';

    /**
     * The mautic.email_on_build event is thrown before displaying the email builder form to allow adding of tokens
     *
     * The event listener receives a
     * Mautic\EmailBundle\Event\EmailEvent instance.
     *
     * @var string
     */
    const EMAIL_ON_BUILD   = 'mautic.email_on_build';

    /**
     * The mautic.email_pre_save event is thrown right before a email is persisted.
     *
     * The event listener receives a
     * Mautic\EmailBundle\Event\EmailEvent instance.
     *
     * @var string
     */
    const EMAIL_PRE_SAVE   = 'mautic.email_pre_save';

    /**
     * The mautic.email_post_save event is thrown right after a email is persisted.
     *
     * The event listener receives a
     * Mautic\EmailBundle\Event\EmailEvent instance.
     *
     * @var string
     */
    const EMAIL_POST_SAVE   = 'mautic.email_post_save';

    /**
     * The mautic.email_pre_delete event is thrown prior to when a email is deleted.
     *
     * The event listener receives a
     * Mautic\EmailBundle\Event\EmailEvent instance.
     *
     * @var string
     */
    const EMAIL_PRE_DELETE   = 'mautic.email_pre_delete';

    /**
     * The mautic.email_post_delete event is thrown after a email is deleted.
     *
     * The event listener receives a
     * Mautic\EmailBundle\Event\EmailEvent instance.
     *
     * @var string
     */
    const EMAIL_POST_DELETE   = 'mautic.email_post_delete';

    /**
     * The mautic.monitored_email_config event is dispatched during the configuration in order to inject custom folder locations
     *
     * The event listener receives a Mautic\CoreBundle\Event\MonitoredEmailEvent instance.
     *
     * @var string
     */
    const MONITORED_EMAIL_CONFIG = 'mautic.monitored_email_config';

    /**
     * The mautic.on_email_parse event is thrown when a monitored email box retrieves messages.
     *
     * The event listener receives a Mautic\EmailBundle\Event\ParseEmailEvent instance.
     *
     * @var string
     */
    const EMAIL_PARSE = 'mautic.on_email_parse';

    /**
     * The mautic.on_email_failed event is thrown when an email has failed to clear the queue and is about to be deleted
     * in order to give a bundle a chance to do an action based on failed email if required
     *
     * The event listener receives a Mautic\EmailBundle\Event\QueueEmailEvent instance.
     *
     * @var string
     */
    const EMAIL_FAILED = 'mautic.on_email_failed';

    /**
     * The mautic.on_email_resend event is thrown when an attempt to resend an email occurs
     * in order to give a bundle a chance to do an action based on failed email if required
     *
     * The event listener receives a Mautic\EmailBundle\Event\QueueEmailEvent instance.
     *
     * @var string
     */
    const EMAIL_RESEND = 'mautic.on_email_resend';

    /**
     * The mautic.email.on_campaign_trigger_action event is fired when the campaign action triggers.
     *
     * The event listener receives a
     * Mautic\CampaignBundle\Event\CampaignExecutionEvent
     *
     * @var string
     */
    const ON_CAMPAIGN_TRIGGER_ACTION = 'mautic.email.on_campaign_trigger_action';

    /**
     * The mautic.email.on_campaign_trigger_decision event is fired when the campaign action triggers.
     *
     * The event listener receives a
     * Mautic\CampaignBundle\Event\CampaignExecutionEvent
     *
     * @var string
     */
    const ON_CAMPAIGN_TRIGGER_DECISION = 'mautic.email.on_campaign_trigger_decision';
}