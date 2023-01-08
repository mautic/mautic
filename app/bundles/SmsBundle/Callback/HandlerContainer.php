<?php

namespace Mautic\SmsBundle\Callback;

use Mautic\SmsBundle\Exception\CallbackHandlerNotFound;

class HandlerContainer
{
    /**
     * @var CallbackInterface[]
     */
    private $handlers;

    public function registerHandler(CallbackInterface $handler)
    {
        $this->handlers[$handler->getTransportName()] = $handler;
    }

    /**
     * @param $transportName
     *
     * @return CallbackInterface
     *
     * @throws CallbackHandlerNotFound
     */
    public function getHandler($transportName)
    {
        if (!isset($this->handlers[$transportName])) {
            throw new CallbackHandlerNotFound("$transportName has not been registered");
        }

        return $this->handlers[$transportName];
    }
}
