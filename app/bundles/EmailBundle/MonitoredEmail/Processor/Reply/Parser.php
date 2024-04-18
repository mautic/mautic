<?php

namespace Mautic\EmailBundle\MonitoredEmail\Processor\Reply;

use Mautic\EmailBundle\MonitoredEmail\Exception\ReplyNotFound;
use Mautic\EmailBundle\MonitoredEmail\Message;

class Parser
{
    public function __construct(
        private Message $message
    ) {
    }

    /**
     * Only sure way is to parse the content for the stat ID otherwise attempt the from.
     *
     * @throws ReplyNotFound
     */
    public function parse(): RepliedEmail
    {
        if (!preg_match('/email\/([a-zA-Z0-9]+)\.gif/', $this->message->textHtml, $parts)) {
            throw new ReplyNotFound();
        }

        $hashId = $parts[1];

        return new RepliedEmail($this->message->fromAddress, $hashId);
    }
}
