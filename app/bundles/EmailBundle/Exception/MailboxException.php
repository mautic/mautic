<?php

namespace Mautic\EmailBundle\Exception;

final class MailboxException extends \Exception
{
    public function __construct($message = null, $code = 0, ?\Exception $previous = null)
    {
        if (null === $message) {
            $message = 'Error communicating with the IMAP server';
        }

        parent::__construct($message, $code, $previous);
    }
}
