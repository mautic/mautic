<?php

namespace Mautic\SmsBundle\Callback;

use Mautic\SmsBundle\Exception\CallbackHandlerNotFound;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class HandlerContainer
{
    /**
     * @var CallbackInterface[]
     */
    private ?array $handlers = null;

    /**
     * @param iterable<CallbackInterface> $callbackHandlers
     */
    public function __construct(
        #[AutowireIterator('mautic.sms_callback_handler')]
        iterable $callbackHandlers = [],
    ) {
        foreach ($callbackHandlers as $callbackHandler) {
            $this->registerHandler($callbackHandler);
        }
    }

    private function registerHandler(CallbackInterface $handler): void
    {
        $this->handlers[$handler->getTransportName()] = $handler;
    }

    /**
     * @return CallbackInterface
     *
     * @throws CallbackHandlerNotFound
     */
    public function getHandler($transportName)
    {
        if (!isset($this->handlers[$transportName])) {
            throw new CallbackHandlerNotFound("{$transportName} has not been registered");
        }

        return $this->handlers[$transportName];
    }
}
