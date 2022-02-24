<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Mailer\Transport;

use Mautic\EmailBundle\MonitoredEmail\Exception\UnsubscriptionNotFound;
use Mautic\EmailBundle\MonitoredEmail\Message;
use Mautic\EmailBundle\MonitoredEmail\Processor\Unsubscription\UnsubscribedEmail;

/**
 * Interface InterfaceUnsubscriptionProcessor.
 */
interface UnsubscriptionProcessorInterface
{
    /**
     * Get the email address that unsubscribed.
     *
     * @throws UnsubscriptionNotFound
     */
    public function processUnsubscription(Message $message): UnsubscribedEmail;
}
