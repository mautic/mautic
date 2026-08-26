<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Exception;

final class MessageOnlyErrorHandlerException extends ErrorHandlerException
{
    public function __construct(string $message = '')
    {
        parent::__construct($message, true);
    }
}
