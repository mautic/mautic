<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\MonitoredEmail\Processor;

use Mautic\EmailBundle\MonitoredEmail\Message;

interface ProcessorInterface
{
    /**
     * Process the message.
     *
     * @return bool|void
     */
    public function process(Message $message);
}
