<?php

declare(strict_types=1);

namespace Mautic\MessengerBundle\Messenger;

use Doctrine\ORM\EntityManagerInterface;
use MauticPlugin\AutomationBundle\Log\SlackChannelLogger;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ErrorDetailsStamp;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;
use Symfony\Component\Messenger\Stamp\SentToFailureTransportStamp;
use Symfony\Component\Messenger\Stamp\TransportMessageIdStamp;

class FailedTransportMiddleware implements MiddlewareInterface
{
    private SlackChannelLogger $logger;
    private EntityManagerInterface $entityManager;

    public function __construct(SlackChannelLogger $log, EntityManagerInterface $entityManager)
    {
        $this->logger        = $log;
        $this->entityManager = $entityManager;
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        try {
            /** @var ?ReceivedStamp $receivedStamp */
            $receivedStamp = $envelope->all(ReceivedStamp::class)[0] ?? null;

            if (null === $receivedStamp || $receivedStamp->getTransportName() !== 'failed') {
                return $stack->next()->handle($envelope, $stack);
            }

            $q = $this->entityManager->getConnection()->fetchOne('select count(*) from '.MAUTIC_TABLE_PREFIX.'messenger_messages');
            $this->logger->info(' instance contains '.$q.' failed messages');

            return $stack->next()->handle($envelope, $stack);

            /** @var RedeliveryStamp[] $redeliveries */
            $redeliveries = $envelope->all(RedeliveryStamp::class);
            /** @var SentToFailureTransportStamp $nativeTransport */
            $nativeTransport = $envelope->last(SentToFailureTransportStamp::class);
            /** @var TransportMessageIdStamp $messageIdStamp */
            $messageIdStamp = $envelope->last(TransportMessageIdStamp::class);

            if (count($redeliveries) < 4) {
                return $stack->next()->handle($envelope, $stack);
            }

            foreach ($redeliveries as $redelivery) {
                $deliveries[] = sprintf(
                    ' * %02d at %s due to **%s**',
                    $redelivery->getRetryCount(),
                    $redelivery->getRedeliveredAt()->format('c'),
                    (string) $redelivery->getExceptionMessage()
                );
            }

            $message = sprintf("[%s] #%s failed after %d deliveries\n%s",
                $messageIdStamp->getId(),
                $nativeTransport->getOriginalReceiverName(),
                count($redeliveries),
                join("\n", $deliveries)
            );

            $this->logger->error($message);
        } catch (\Exception $e) {
            $envelope = $envelope->with(new ErrorDetailsStamp(get_class($e), 400, $e->getMessage(), FlattenException::createFromThrowable($e)));
        }

        return $stack->next()->handle($envelope, $stack);
    }
}
