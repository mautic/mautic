<?php

declare(strict_types=1);

namespace Mautic\MessengerBundle\Exceptions;

use Symfony\Component\Messenger\Exception\UnrecoverableExceptionInterface;

class InvalidPayloadException extends MauticMessengerException implements UnrecoverableExceptionInterface
{
    /**
     * @param array<mixed> $payload
     */
    public function __construct(string $message = '', array $payload = [], \Throwable $previous = null)
    {
        $message .= json_encode($payload);
        parent::__construct($message, 400, $previous);
    }
}
