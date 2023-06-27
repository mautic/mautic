<?php

declare(strict_types=1);

namespace Mautic\MessengerBundle\MessageHandler;

use Doctrine\ORM\OptimisticLockException;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\MessengerBundle\Exceptions\MauticMessengerException;
use Mautic\MessengerBundle\Factory\MessengerRequestFactory;
use Mautic\MessengerBundle\MauticMessengerBundle;
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

    /** @throws MauticMessengerException */
    public function __invoke(EmailHitNotification $message, Acknowledger $ack = null)
    {
        $hitDateTime = (new DateTimeHelper($message->getEventTime()))->getDateTime();

        try {
            $this->logger->debug(MauticMessengerBundle::LOG_PREFIX.'processing email hit notification, statId '.$message->getStatId());
            $this->emailModel->hitEmail(
                $message->getStatId(),
                MessengerRequestFactory::fromArray($message->getRequest()),
                false,
                $message->isSynchronousRequest(),
                $hitDateTime,
                true
            );
        } catch (OptimisticLockException $lockException) {
            throw new RecoverableMessageHandlingException($lockException->getMessage());
        } catch (\Exception $exception) {
            $this->logger->error(MauticMessengerBundle::LOG_PREFIX.$exception->getMessage(), (array) $exception);
            printf("Failed email hit #%s with %s\n", $message->getStatId(), $exception->getMessage());
            throw new MauticMessengerException(MauticMessengerBundle::LOG_PREFIX.$exception->getMessage(), 400, $exception);
        }
    }

    public static function getHandledMessages(): iterable
    {
        yield EmailHitNotification::class => [];
    }
}
