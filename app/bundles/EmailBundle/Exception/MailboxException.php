<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Exception;

final class MailboxException extends \Exception
{
    public function __construct(?string $message = null, int $code = 0, ?\Exception $previous = null)
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
