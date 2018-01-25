<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\MonitoredEmail\Processor\Unsubscription;

use Mautic\EmailBundle\MonitoredEmail\Exception\UnsubscriptionNotFound;
use Mautic\EmailBundle\MonitoredEmail\Message;

class Parser
{
    /**
     * @var Message
     */
    protected $message;

    /**
     * Parser constructor.
     *
     * @param Message $message
     */
    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    /**
     * @return UnsubscribedEmail
     *
     * @throws UnsubscriptionNotFound
     */
    public function parse()
    {
        $unsubscriptionEmail = null;
        foreach ($this->message->to as $to => $name) {
            if (strpos($to, '+unsubscribe') !== false) {
                $unsubscriptionEmail = $to;

                break;
            }
        }

        if (!$unsubscriptionEmail) {
            throw new UnsubscriptionNotFound();
        }

        return new UnsubscribedEmail($this->message->fromAddress, $unsubscriptionEmail);
    }
}
