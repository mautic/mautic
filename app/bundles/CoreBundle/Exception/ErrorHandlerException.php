<?php

namespace Mautic\CoreBundle\Exception;

class ErrorHandlerException extends \Exception
{
    /**
     * @param string $message
     * @param bool   $showMessage
     * @param int    $code
     */
    public function __construct(
        $message = '',
        protected $showMessage = false,
        $code = 0,
        \Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return bool
     */
    public function showMessage()
    {
        return $this->showMessage;
    }
}
