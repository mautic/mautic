<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
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
     * The mautic.email.swiftmailer.sendgrid_api.add_categories event is
     * dispatched by the mautic.transport.sendgrid_api.mail.categories service
     * when its addCategoriesToMail method is called by the SendGridApiMessage
     * class as the last step before returning a SendGrid\Mail instance in the
     * getMessage method.
     *
     * The event listener receives an instance of
     * Mautic\EmailBundle\Swiftmailer\SendGrid\Event\SendGridMailCategoriesEvent.
     *
     * @var string
     */
    const ADD_CATEGORIES = 'mautic.email.swiftmailer.sendgrid_api.add_categories';
}
