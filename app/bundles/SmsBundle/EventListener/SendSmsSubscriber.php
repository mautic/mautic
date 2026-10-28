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

final readonly class SendSmsSubscriber
{
    public function __construct(
        private DoNotContactRepository $dncRepo,
        private MessageQueueModel $messageQueueModel,
    ) {
    }
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(
        event: DncEvent::class,
        priority: 0
    )]
    public function dncFilter(DncEvent $event): void
    {
        $dnc = $this->dncRepo->getChannelList('sms', array_keys($event->getContacts()));

        if ([] === $dnc) {
            return;
        }

        $event->removeContacts(array_keys($dnc));
    }
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(
        event: QueueEvent::class,
        priority: 0
    )]
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
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(
        event: FilterEvent::class,
        priority: 0
    )]
    public function genericFilter(FilterEvent $event): void
    {
        $contactsWithoutNumbers = array_filter($event->getContacts(), fn (Lead $contact): bool => empty($contact->getLeadPhoneNumber()));

        $event->removeContacts(array_map(fn (Lead $contact): int => $contact->getId(), $contactsWithoutNumbers));
    }
}
