<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Event;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class EmailEvent
 *
 * @package Mautic\CoreBundle\Event
 */
class EmailEvent extends Event
{
    private $message;
    private $retry = false;

    public function __construct(\Swift_Message $message)
    {
        $this->message = $message;
    }

    /**
     * @return \Swift_Message
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Sets whether the sending of the message should be tried again
     */
    public function tryAgain()
    {
        $this->retry = true;
    }

    /**
     * @return bool
     */
    public function shouldTryAgain()
    {
        return $this->retry;
    }
}