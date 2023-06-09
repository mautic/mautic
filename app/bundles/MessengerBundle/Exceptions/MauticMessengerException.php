<?php

declare(strict_types=1);

namespace Mautic\MessengerBundle\Exceptions;

use Exception;
use Mautic\MessengerBundle\MauticMessengerBundle;
use Throwable;

class MauticMessengerException extends Exception
{
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        $message = MauticMessengerBundle::LOG_PREFIX.$message;
        parent::__construct($message, $code, $previous);
    }
}
