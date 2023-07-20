<?php

declare(strict_types=1);

namespace Mautic\MessengerBundle\MessageHandler;

use Doctrine\ORM\OptimisticLockException;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\MessengerBundle\Message\EmailHitNotification;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Exception\RecoverableMessageHandlingException;
use Symfony\Component\Messenger\Handler\Acknowledger;
use Symfony\Component\Messenger\Handler\MessageSubscriberInterface;

class EmailHitNotificationHandler implements MessageSubscriberInterface
{
    public function __construct(private EmailModel $emailModel, private LoggerInterface $logger)
    {
    }

    public function __invoke(EmailHitNotification $message, Acknowledger $ack = null): void
    {
        try {
            $this->logger->debug('Processing email hit notification, statId '.$message->getStatId());
            $this->emailModel->hitEmail(
                $message->getStatId(),
                $message->getRequest(),
                false,
                $message->isSynchronousRequest(),
                $message->getEventTime(),
                true
            );
        } catch (OptimisticLockException $lockException) {
            throw new RecoverableMessageHandlingException($lockException->getMessage());
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage(), (array) $exception);
            throw $exception;
        }
    }

    /**
     * @return iterable<string, mixed>
     */
    public static function getHandledMessages(): iterable
    {
        yield EmailHitNotification::class => [];
    }
}
