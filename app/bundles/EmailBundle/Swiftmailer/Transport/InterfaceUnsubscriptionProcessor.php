<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Swiftmailer\Transport;

use Mautic\EmailBundle\MonitoredEmail\Exception\UnsubscriptionNotFound;
use Mautic\EmailBundle\MonitoredEmail\Message;
use Mautic\EmailBundle\MonitoredEmail\Processor\Unsubscription\UnsubscribedEmail;

/**
 * Interface InterfaceUnsubscriptionProcessor.
 */
interface InterfaceUnsubscriptionProcessor
{
    /**
     * Get the email address that unsubscribed.
     *
     * @param Message $message
     *
     * @return UnsubscribedEmail
     *
     * @throws UnsubscriptionNotFound
     */
    public function processUnsubscription(Message $message);
}
