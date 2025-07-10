<?php

declare(strict_types=1);

namespace Mautic\MessengerBundle\MessageHandler;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\MessengerBundle\Message\TestEmail;
use Mautic\MessengerBundle\Message\TestFailed;
use Mautic\MessengerBundle\Message\TestHit;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\Messenger\Handler\MessageSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @deprecated since Mautic 5.0, to be removed in 6.0 with no replacement.
 */
class TestHandler implements MessageSubscriberInterface
{
    public function __construct(
        private NotificationModel $notificationModel,
        private EntityManagerInterface $entityManager,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @return iterable<class-string, array<string, string>>
     */
    public static function getHandledMessages(): iterable
    {
        yield TestEmail::class => ['method' => 'handleEmail'];
        yield TestHit::class => ['method' => 'handleHit'];
        yield TestFailed::class => ['method' => 'handleFailed'];
    }

    public function handleEmail(TestEmail $message): void
    {
        $this->sendNotification($message->userId, 'email');
    }

    public function handleHit(TestHit $message): void
    {
        $this->sendNotification($message->userId, 'hit');
    }

    public function handleFailed(TestFailed $message): void
    {
        $this->sendNotification($message->userId, 'failed');
    }

    private function sendNotification(int $userId, string $type): void
    {
        $this->notificationModel->addNotification(
            $this->translator->trans('mautic.messenger.config.dsn.test_message_processed', ['%type%' => $type]),
            null,
            false,
            null,
            null,
            null,
            $this->entityManager->getReference(User::class, $userId),
        );
    }
}
