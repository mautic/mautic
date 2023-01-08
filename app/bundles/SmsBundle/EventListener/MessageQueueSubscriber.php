<?php

namespace Mautic\SmsBundle\EventListener;

use Mautic\ChannelBundle\ChannelEvents;
use Mautic\ChannelBundle\Entity\MessageQueue;
use Mautic\ChannelBundle\Event\MessageQueueBatchProcessEvent;
use Mautic\SmsBundle\Model\SmsModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MessageQueueSubscriber implements EventSubscriberInterface
{
    /**
     * @var SmsModel
     */
    private $model;

    public function __construct(SmsModel $model)
    {
        $this->model = $model;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            ChannelEvents::PROCESS_MESSAGE_QUEUE_BATCH => ['onProcessMessageQueueBatch', 0],
        ];
    }

    /**
     * Sends campaign emails.
     */
    public function onProcessMessageQueueBatch(MessageQueueBatchProcessEvent $event)
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
            if ($sms && $message->getLead() && $sms->isPublished()) {
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
            $options['resend_message_queue'] = $messagesByContact;
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
