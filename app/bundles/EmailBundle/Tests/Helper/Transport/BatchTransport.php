<?php

/*
 * Created by PhpStorm.
 * User: alan
 * Date: 9/14/16
 * Time: 5:42 PM.
 */

namespace Mautic\EmailBundle\Tests\Helper\Transport;

use Mautic\EmailBundle\Swiftmailer\Transport\AbstractTokenArrayTransport;

class BatchTransport extends AbstractTokenArrayTransport implements \Swift_Transport
{
    public function send(\Swift_Mime_Message $message, &$failedRecipients = null)
    {
    }

    public function getMaxBatchLimit()
    {
        return 1;
    }

    public function getBatchRecipientCount(\Swift_Message $message, $toBeAdded = 1, $type = 'to')
    {
        return count($message->getTo()) + $toBeAdded;
    }
}
