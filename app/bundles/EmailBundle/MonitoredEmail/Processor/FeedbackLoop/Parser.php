<?php

namespace Mautic\EmailBundle\MonitoredEmail\Processor\FeedbackLoop;

use Mautic\EmailBundle\MonitoredEmail\Exception\FeedbackLoopNotFound;
use Mautic\EmailBundle\MonitoredEmail\Message;
use Mautic\EmailBundle\MonitoredEmail\Processor\Address;

class Parser
{
    /**
     * @var Message
     */
    private $message;

    /**
     * Parser constructor.
     */
    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    /**
     * @return string|null
     *
     * @throws FeedbackLoopNotFound
     */
    public function parse()
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
     *
     * @return string|null
     */
    protected function searchMessage($pattern, $content)
    {
        if (preg_match('/'.$pattern.'/i', $content, $match)) {
            if ($parsedAddressList = Address::parseList($match[1])) {
                return key($parsedAddressList);
            }
        }
    }
}
