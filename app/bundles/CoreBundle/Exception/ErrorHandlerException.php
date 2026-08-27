<?php

namespace Mautic\CoreBundle\Exception;

class ErrorHandlerException extends \Exception
{
    /**
     * @param string $message
     * @param int    $code
     */
    public function __construct(
        $message = '',
        protected bool $showMessage = false,
        $code = 0,
        ?\Exception $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function showMessage(): bool
    {
        return $this->showMessage;
    }
}
