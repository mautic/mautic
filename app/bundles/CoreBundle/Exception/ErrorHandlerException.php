<?php

namespace Mautic\CoreBundle\Exception;

use Exception;

class ErrorHandlerException extends \Exception
{
    /**
     * @var bool
     */
    protected $showMessage = false;

    /**
     * ErrorHandlerException constructor.
     *
     * @param string $message
     * @param bool   $showMessage
     * @param int    $code
     */
    public function __construct($message = '', $showMessage = false, $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->showMessage = $showMessage;
    }

    /**
     * @return bool
     */
    public function showMessage()
    {
        return $this->showMessage;
    }
}
