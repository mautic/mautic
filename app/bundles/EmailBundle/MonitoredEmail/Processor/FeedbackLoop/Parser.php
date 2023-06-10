<?php

namespace Mautic\EmailBundle\MonitoredEmail\Processor\FeedbackLoop;

use Mautic\EmailBundle\MonitoredEmail\Exception\FeedbackLoopNotFound;
use Mautic\EmailBundle\MonitoredEmail\Message;
use Mautic\EmailBundle\MonitoredEmail\Processor\Address;

class Parser
{
    /**
     * Parser constructor.
     */
    public function __construct(private Message $message)
    {
    }

    /**
     * @throws FeedbackLoopNotFound
     */
    public function parse(): ?string
    {
        if (null === $this->message->fblReport) {
            throw new FeedbackLoopNotFound();
        }

        if ($email = $this->searchMessage('Original-Rcpt-To: (.*)', $this->message->fblReport)) {
            return $email;
        }

        if ($email = $this->searchMessage('Received:.*for (.*);.*?', $this->message->textPlain)) {
            return $email;
        }

        throw new FeedbackLoopNotFound();
    }

    /**
     * @param string $content
     * @param string $pattern
     */
    protected function searchMessage($pattern, $content): ?string
    {
        if (preg_match('/'.$pattern.'/i', $content, $match)) {
            if ($parsedAddressList = Address::parseList($match[1])) {
                return key($parsedAddressList);
            }
        }
    }
}
