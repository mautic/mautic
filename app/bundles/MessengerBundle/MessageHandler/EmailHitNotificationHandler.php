<?php

declare(strict_types=1);

namespace Mautic\MessengerBundle\MessageHandler;

use Doctrine\DBAL\Exception\RetryableException;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\MessengerBundle\Message\EmailHitNotification;
use Symfony\Component\Messenger\Exception\RecoverableMessageHandlingException;
use Symfony\Component\Messenger\Handler\Acknowledger;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class EmailHitNotificationHandler implements MessageHandlerInterface
{
    private bool $isSyncTransport;

    public function __construct(
        private EmailModel $emailModel,
        CoreParametersHelper $parametersHelper
    ) {
        $this->isSyncTransport = str_starts_with($parametersHelper->get('messenger_dsn_hit'), 'sync://');
    }

    public function __invoke(EmailHitNotification $message, Acknowledger $ack = null): void
    {
        try {
            $this->emailModel->hitEmail(
                $message->getStatId(),
                $message->getRequest(),
                false,
                $this->isSyncTransport,
                $message->getEventTime(),
                true
            );
        } catch (RetryableException $e) {
            throw new RecoverableMessageHandlingException($e->getMessage());
        }
    }
}
