<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Swiftmailer\SendGrid;

final class SendGridMailEvents
{
    /**
     * The mautic.email.swiftmailer.sendgrid_api.get_mail_message event is
     * dispatched by the mautic.transport.sendgrid_api.message service
     * as the last step before returning a SendGrid\Mail instance in the
     * getMessage method.
     *
     * The event listener receives an instance of
     * \Mautic\EmailBundle\Swiftmailer\SendGrid\Event\GetMailMessageEvent.
     *
     * @var string
     */
    const GET_MAIL_MESSAGE = 'mautic.email.swiftmailer.sendgrid_api.get_mail_message';
}
