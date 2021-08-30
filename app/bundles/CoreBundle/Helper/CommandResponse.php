<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper;

final class CommandResponse
{
    /*
     * @var int
     */
    private $statusCode;

    /*
     * @var string
     */
    private $message;

    public function __construct(int $statusCode, string $message)
    {
        $this->statusCode = $statusCode;
        $this->message    = $message;
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
