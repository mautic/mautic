<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional\Event;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Event\ContactLastActiveUpdatedEvent;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class ContactLastActiveUpdatedEventFunctionalTest extends MauticMysqlTestCase
{
    /**
     * @var ContactLastActiveUpdatedEvent[]
     */
    private array $events = [];

    protected function setUp(): void
    {
        parent::setUp();

        self::getContainer()->get(EventDispatcherInterface::class)->addListener(
            ContactLastActiveUpdatedEvent::class,
            function (ContactLastActiveUpdatedEvent $event): void {
                $this->events[] = $event;
            }
        );
    }

    public function testRepositoryUpdateDispatchesEventWithPreviousDate(): void
    {
        $contact = $this->createContact(new \DateTime('2026-01-01 10:00:00', new \DateTimeZone('UTC')));

        self::getContainer()->get(LeadRepository::class)->updateLastActive(
            $contact->getId(),
            new \DateTime('2026-02-01 12:00:00', new \DateTimeZone('UTC'))
        );

        $this->assertCount(1, $this->events);
        $this->assertSame($contact->getId(), $this->events[0]->getContactId());
        $this->assertSame('2026-02-01 12:00:00', $this->events[0]->getLastActive()->format('Y-m-d H:i:s'));
        $this->assertSame('2026-01-01 10:00:00', $this->events[0]->getPreviousLastActive()?->format('Y-m-d H:i:s'));
    }

    public function testSavingContactWithChangedLastActiveDispatchesEvent(): void
    {
        $contact = $this->createContact(null);

        $lastActive = new \DateTime('2026-03-01 08:00:00', new \DateTimeZone('UTC'));
        $contact->setLastActive($lastActive);
        self::getContainer()->get(LeadModel::class)->saveEntity($contact);

        $this->assertCount(1, $this->events);
        $this->assertSame($contact->getId(), $this->events[0]->getContactId());
        $this->assertSame($lastActive, $this->events[0]->getLastActive());
        $this->assertNull($this->events[0]->getPreviousLastActive());
    }

    public function testSavingContactWithoutLastActiveChangeDispatchesNoEvent(): void
    {
        $contact = $this->createContact(new \DateTime('2026-01-01 10:00:00', new \DateTimeZone('UTC')));

        $contact->setFirstname('Changed');
        self::getContainer()->get(LeadModel::class)->saveEntity($contact);

        $this->assertCount(0, $this->events);
    }

    private function createContact(?\DateTime $lastActive): Lead
    {
        $contact = new Lead();
        $contact->setEmail('last-active@example.com');
        $contact->setLastActive($lastActive);
        $this->em->persist($contact);
        $this->em->flush();
        $contact->resetChanges();

        return $contact;
    }
}
