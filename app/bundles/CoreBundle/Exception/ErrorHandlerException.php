<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Exception;

class ErrorHandlerException extends \Exception
{
    public function __construct(
        string $message = '',
        protected bool $showMessage = false,
        int $code = 0,
        ?\Exception $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function showMessage(): bool
    {
        return $this->showMessage;
    }
}
