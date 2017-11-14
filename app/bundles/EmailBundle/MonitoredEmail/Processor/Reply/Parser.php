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
     * @return null|string
     */
    public function parse()
    {
        if (preg_match('/Received:.*for (.*);.*?/isU', $this->message->textPlain, $match)) {
            if ($parsedAddressList = Address::parseList($match[1])) {
                return key($parsedAddressList);
            }
        }

        return null;
    }
}
