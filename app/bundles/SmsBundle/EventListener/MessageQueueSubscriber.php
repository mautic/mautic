<?php

namespace Mautic\SmsBundle\EventListener;

use Mautic\ChannelBundle\Entity\MessageQueue;
use Mautic\ChannelBundle\Event\MessageQueueBatchProcessEvent;
use Mautic\SmsBundle\Entity\Sms;
use Mautic\SmsBundle\Model\SmsModel;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class MessageQueueSubscriber
{
    public function __construct(
        private SmsModel $model,
    ) {
    }

    /**
     * Sends campaign emails.
     */
    #[AsEventListener(
        event: MessageQueueBatchProcessEvent::class,
    )]
    public function onProcessMessageQueueBatch(MessageQueueBatchProcessEvent $event): void
    {
        if (!$event->checkContext('sms')) {
            return;
        }

        $messages          = $event->getMessages();
        $id                = $event->getChannelId();
        $sms               = $this->model->getEntity($id);
        $sendTo            = [];
        $messagesByContact = [];

        /** @var MessageQueue $message */
        foreach ($messages as $message) {
            if ($sms instanceof Sms && $message->getLead() && $sms->isPublished()) {
                $contact = $message->getLead();
                $mobile  = $contact->getMobile();
                $phone   = $contact->getPhone();
                if (empty($mobile) && empty($phone)) {
                    $message->setProcessed();
                    $message->setSuccess();
                }
                $sendTo[$contact->getId()]            = $contact;
                $messagesByContact[$contact->getId()] = $message;
            } else {
                $message->setFailed();
            }
        }

        if (count($sendTo)) {
            $options                         = ['resend_message_queue' => $messagesByContact];
            $results                         = $this->model->sendSms($sms, $sendTo, $options);

            foreach ($messagesByContact as $contactId => $message) {
                if (!$message->isProcessed()) {
                    $message->setProcessed();
                    $message->setMetadata($results[$contactId]);
                    if ($results[$contactId]['sent']) {
                        $message->setSuccess();
                    }
                }
            }
        }

        $event->stopPropagation();
    }
}
