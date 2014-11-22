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
}