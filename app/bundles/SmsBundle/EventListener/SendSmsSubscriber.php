<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\EventListener;

use Mautic\ChannelBundle\Entity\MessageQueue;
use Mautic\ChannelBundle\Model\MessageQueueModel;
use Mautic\LeadBundle\Entity\DoNotContactRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\SmsBundle\Event\DncEvent;
use Mautic\SmsBundle\Event\FilterEvent;
use Mautic\SmsBundle\Event\QueueEvent;
use Mautic\SmsBundle\SmsEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class SendSmsSubscriber implements EventSubscriberInterface
{
    public function __construct(private DoNotContactRepository $dncRepo, private MessageQueueModel $messageQueueModel)
    {
    }

    /**
     * @return array<string, array<mixed>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            SmsEvents::DNC_FILTER_CONTACTS_ON_SEND   => ['dncFilter', 0],
            SmsEvents::QUEUE_FILTER_CONTACTS_ON_SEND => ['queueFilter', 0],
            SmsEvents::FILTER_CONTACTS_ON_SEND       => ['genericFilter', 0],
        ];
    }

    public function dncFilter(DncEvent $event): void
    {
        $dnc = $this->dncRepo->getChannelList('sms', array_keys($event->getContacts()));

        if (!$dnc) {
            return;
        }

        $event->removeContacts(array_keys($dnc));
    }

    public function queueFilter(QueueEvent $event): void
    {
        $options         = $event->getOptions();
        $messageQueue    = $options['resend_message_queue'] ?? null;
        $channel         = $options['channel'] ?? null;
        $campaignEventId = (is_array($channel) && 'campaign.event' === $channel[0] && !empty($channel[1])) ? $channel[1] : null;

        $contacts       = $event->getContacts();
        $queuedContacts = $this->messageQueueModel->processFrequencyRules(
            $contacts,
            'sms',
            $options['sms_id'] ?? '',
            $campaignEventId,
            3,
            MessageQueue::PRIORITY_NORMAL,
            $messageQueue,
            'sms_message_stats'
        );

        $event->queueContacts($queuedContacts);
    }

    public function genericFilter(FilterEvent $event): void
    {
        $contactsWithoutNumbers = array_filter($event->getContacts(), fn (Lead $contact): bool => empty($contact->getLeadPhoneNumber()));

        $event->removeContacts(array_map(fn (Lead $contact): int => $contact->getId(), $contactsWithoutNumbers));
    }
}
