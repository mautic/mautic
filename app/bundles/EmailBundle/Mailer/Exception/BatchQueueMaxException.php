<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Mailer\Exception;

class BatchQueueMaxException extends \Exception
{
    public function __construct(string $message = 'Max number of emails have been queued. Run flushQueue() first then queue() again', int $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
