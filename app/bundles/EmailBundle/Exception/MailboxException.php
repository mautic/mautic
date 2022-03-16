<?php

namespace Mautic\EmailBundle\Exception;

class MailboxException extends \Exception
{
    public function __construct($message = null, $code = 0, \Exception $previous = null)
    {
        if (null === $message) {
            $message = 'Error communicating with the IMAP server';

            if (function_exists('imap_last_error')) {
                $message .= ': '.imap_last_error();
            }
        }

        parent::__construct($message, $code, $previous);
    }
}
