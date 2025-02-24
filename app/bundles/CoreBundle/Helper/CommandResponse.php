<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper;

final class CommandResponse
{
    public function __construct(
        private int $statusCode,
        private string $message
    ) {
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
