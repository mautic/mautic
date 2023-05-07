<?php

declare(strict_types=1);

namespace Mautic\MessengerBundle\MessageHandler;

use Mautic\MessengerBundle\Exceptions\MauticMessengerException;
use Mautic\MessengerBundle\Message\EmailHitNotification;
use Mautic\MessengerBundle\Message\PageHitNotification;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Exception\RecoverableMessageHandlingException;
use Symfony\Component\Messenger\Handler\Acknowledger;
use Symfony\Component\Messenger\Handler\MessageSubscriberInterface;

class FailedMessageHandler implements MessageSubscriberInterface
{
    public const NOTIFICATION_SEND_FREQUENCY_S = 30;
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /** @throws MauticMessengerException */
    public function __invoke($message, Acknowledger $ack = null)
    {
        dump($message);
        $this->logger->error('ajajaja:'.json_encode($message));

        throw new RecoverableMessageHandlingException('has been reported');
    }

    public static function getHandledMessages(): iterable
    {
        yield PageHitNotification::class => [
            'from_transport' => 'failed',
            'priority'       => -1,
        ];
        yield EmailHitNotification::class => [
            'from_transport' => 'failed',
            'priority'       => -1,
        ];
    }
}
