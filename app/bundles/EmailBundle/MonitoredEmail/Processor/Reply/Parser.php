<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\MonitoredEmail\Processor\Reply;

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
     * Only sure way is to parse the content for the stat ID otherwise attempt the from.
     *
     * @return RepliedEmail
     */
    public function parse()
    {
        $hashId = null;
        if (preg_match('/email\/(.*?)\.gif/', $this->message->textHtml, $parts)) {
            $hashId = $parts[1];
        }

        return new RepliedEmail($this->message->fromAddress, $hashId);
    }
}
