<?php

namespace Mautic\SmsBundle\Exception;

use Throwable;

class NumberNotFoundException extends \Exception
{
    /***
     * @var string
     */
    private $number;

    /**
     * NumberNotFoundException constructor.
     *
     * @param string $number
     * @param string $message
     * @param int    $code
     */
    public function __construct($number, $message = '', $code = 0, Throwable $previous = null)
    {
        $this->number = $number;

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
