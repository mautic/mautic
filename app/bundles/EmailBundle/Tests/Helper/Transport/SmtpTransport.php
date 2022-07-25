<?php

namespace Mautic\EmailBundle\Tests\Helper\Transport;

use Swift_Mime_SimpleMessage;

class SmtpTransport implements \Swift_Transport
{
    public function send(Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
    {
    }

    /**
     * Test if this Transport mechanism has started.
     *
     * @return bool
     */
    public function isStarted()
    {
        return true;
    }

    /**
     * Start this Transport mechanism.
     */
    public function start()
    {
        return true;
    }

    /**
     * Stop this Transport mechanism.
     */
    public function stop()
    {
        return true;
    }

    /**
     * Register a plugin in the Transport.
     */
    public function registerPlugin(\Swift_Events_EventListener $plugin)
    {
    }

    /**
     * @return bool
     */
    public function ping()
    {
        return true;
    }
}
