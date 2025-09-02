<?php

declare(strict_types=1);

namespace Mautic\WebhookBundle\Exception;

class PrivateAddressException extends \Exception
{
    private const DEFAULT_MESSAGE = 'Access to private addresses is not allowed.';

    public function __construct(string $message = self::DEFAULT_MESSAGE, int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
