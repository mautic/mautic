<?php

namespace Mautic\EmailBundle\Exception;

use Mautic\CoreBundle\Exception\InvalidValueException;
use Throwable;

class InvalidEmailException extends InvalidValueException
{
    /**
     * @var string
     */
    protected $emailAddress;

    /**
     * @param string $emailAddress
     * @param string $message
     * @param int    $code
     */
    public function __construct($emailAddress, $message = '', $code = 0, Throwable $previous = null)
    {
        $this->emailAddress = $emailAddress;

        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string
     */
    public function getEmailAddress()
    {
        return $this->emailAddress;
    }
}
