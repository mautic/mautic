<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Exception;

final class NumberNotFoundException extends \Exception
{
    public function __construct(
        private readonly string $number,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        if (!$message) {
            $message = "Phone number '{$number}' not found";
        }

        parent::__construct($message, $code, $previous);
    }

    public function getNumber(): string
    {
        return $this->number;
    }
}
