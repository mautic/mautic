<?php

namespace Mautic\EmailBundle\Swiftmailer\Exception;

class BatchQueueMaxException extends \Exception
{
    public function __construct($message = 'Max number of emails have been queued. Run flushQueue() first then queue() again', $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
