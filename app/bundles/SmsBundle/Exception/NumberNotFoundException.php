<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Exception;

final class NumberNotFoundException extends \Exception
{
    /**
     * @param string $number
     */
    public function __construct(
        private $number,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        if (!$message) {
            $message = "Phone number '{$number}' not found";
        }

        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string
     */
    public function getNumber()
    {
        return $this->number;
    }
}
