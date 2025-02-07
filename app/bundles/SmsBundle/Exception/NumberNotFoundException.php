<?php

namespace Mautic\SmsBundle\Exception;

class NumberNotFoundException extends \Exception
{
    /**
     * @param string $number
     * @param string $message
     * @param int    $code
     */
    public function __construct(
        private $number,
        $message = '',
        $code = 0,
        \Throwable $previous = null,
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
