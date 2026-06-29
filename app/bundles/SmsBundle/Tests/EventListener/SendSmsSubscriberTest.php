<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Tests\EventListener;

use Mautic\ChannelBundle\Model\MessageQueueModel;
use Mautic\CoreBundle\Test\ReflectionHelper;
use Mautic\LeadBundle\Entity\DoNotContactRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\SmsBundle\Event\DncEvent;
use Mautic\SmsBundle\Event\FilterEvent;
use Mautic\SmsBundle\Event\QueueEvent;
use Mautic\SmsBundle\EventListener\SendSmsSubscriber;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SendSmsSubscriberTest extends TestCase
{
    private SendSmsSubscriber $subscriber;

    private DoNotContactRepository&MockObject $dncRepoMock;

    private MessageQueueModel&MockObject $mqmMock;

    protected function setUp(): void
    {
        $this->subscriber = new SendSmsSubscriber(
            $this->dncRepoMock = $this->createMock(DoNotContactRepository::class),
            $this->mqmMock     = $this->createMock(MessageQueueModel::class)
        );
    }

    public function testDncFilterNoEntriesDoesNotRemoveContacts(): void
    {
        $this->dncRepoMock->method('getChannelList')
            ->willReturn([]);

        $event = new DncEvent($contacts = [
            1 => new Lead(),
            2 => new Lead(),
        ]);

        $this->subscriber->dncFilter($event);

        $this->assertSame($contacts, $event->getContacts());
    }

    public function testDncFilterContactWithDncIsRemoved(): void
    {
        $this->dncRepoMock->method('getChannelList')
            ->willReturn($contactToRemove = [
                1 => new Lead(),
            ]);

        $event = new DncEvent(array_merge([
            2 => new Lead(),
        ], $contactToRemove));

        $this->subscriber->dncFilter($event);

        $this->assertCount(1, $event->getContacts());
        $this->assertCount(1, $event->getRemovedContacts());
    }

    public function testQueueFilterNoEntriesDoesNotRemoveContacts(): void
    {
        $this->mqmMock->method('processFrequencyRules')
            ->willReturn([]);

        $event = new QueueEvent($contacts = [
            1 => new Lead(),
            2 => new Lead(),
        ], []);

        $this->subscriber->queueFilter($event);

        $this->assertSame($contacts, $event->getContacts());
    }

    public function testQueueFilterContactWithFrequencyRuleIsRemoved(): void
    {
        $lead1 = (new Lead())->setId(1);
        $lead2 = (new Lead())->setId(2);

        $this->mqmMock->method('processFrequencyRules')
            ->willReturn([$lead1->getId()]);

        $event = new QueueEvent(array_merge([2 => $lead2], [1 => $lead1]), []);

        $this->subscriber->queueFilter($event);

        $this->assertCount(1, $event->getContacts());
        $this->assertCount(1, $event->getQueuedContacts());
    }

    public function testGenericFilterContactsWithPhoneNumbersAreNotRemoved(): void
    {
        $event = new FilterEvent($contacts = [
            1 => $contact1 = new Lead(),
            2 => $contact2 = new Lead(),
        ]);

        ReflectionHelper::setValue($contact1, 'id', 1);
        $contact1->setPhone('+1234567890');
        ReflectionHelper::setValue($contact2, 'id', 2);
        $contact2->setPhone('+1234567890');

        $this->subscriber->genericFilter($event);

        $this->assertSame($contacts, $event->getContacts());
    }

    public function testGenericFilterContactsWithoutPhoneNumbersAreRemoved(): void
    {
        $event = new FilterEvent($contacts = [
            1 => $contact1 = new Lead(),
            2 => $contact2 = new Lead(),
        ]);

        ReflectionHelper::setValue($contact1, 'id', 1);
        ReflectionHelper::setValue($contact2, 'id', 2);

        $this->subscriber->genericFilter($event);

        $this->assertCount(0, $event->getContacts());
        $this->assertCount(2, $event->getRemovedContacts());
    }
}
