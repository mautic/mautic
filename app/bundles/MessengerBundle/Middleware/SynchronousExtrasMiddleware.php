<?php

declare(strict_types=1);


namespace Mautic\MessengerBundle\Middleware;

use Mautic\MessengerBundle\MauticMessengerRoutes;
use Mautic\MessengerBundle\Message\Interfaces\RequestStatusInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\LegacyEventDispatcherProxy;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\SendMessageToTransportsEvent;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Messenger\Stamp\SentStamp;
use Symfony\Component\Messenger\Transport\Sender\SendersLocatorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @author Jan Kozak <galvani78@gmail.com>
 *
 * Adds a stamp for request handling
 */
class SynchronousExtrasMiddleware implements MiddlewareInterface
{
    use LoggerAwareTrait;

    public function __construct(private SendersLocatorInterface $sendersLocator)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $sender = key(iterator_to_array($this->sendersLocator->getSenders($envelope)));
        if ($sender !== MauticMessengerRoutes::SYNC || !$envelope->getMessage() instanceof RequestStatusInterface) {
            return $stack->next()->handle($envelope, $stack);
        }

        if (!$envelope->all(ReceivedStamp::class)) { // Set only if not received from AMQP
            $envelope->getMessage()->setIsSynchronousRequest(true);
        }

        return $stack->next()->handle($envelope, $stack);
    }
}
