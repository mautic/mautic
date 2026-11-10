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

    public function testRepositoryUpdateWithUnchangedDateDispatchesNoEvent(): void
    {
        $lastActive = new \DateTime('2026-01-01 10:00:00', new \DateTimeZone('UTC'));
        $contact    = $this->createContact($lastActive);

        self::getContainer()->get(LeadRepository::class)->updateLastActive($contact->getId(), $lastActive);

        $this->assertCount(0, $this->events);
    }

    public function testRepositoryUpdateForDeletedContactDispatchesNoEvent(): void
    {
        $contact   = $this->createContact(null);
        $contactId = $contact->getId();
        $this->em->remove($contact);
        $this->em->flush();

        self::getContainer()->get(LeadRepository::class)->updateLastActive($contactId, new \DateTime('2026-02-01 12:00:00', new \DateTimeZone('UTC')));

        $this->assertCount(0, $this->events);
    }

    public function testRepositoryUpdateFollowedBySaveDispatchesOneEvent(): void
    {
        $contact = $this->createContact(new \DateTime('2026-01-01 10:00:00', new \DateTimeZone('UTC')));

        self::getContainer()->get(LeadRepository::class)->updateLastActive($contact->getId());
        $contact->setFirstname('Changed');
        self::getContainer()->get(LeadModel::class)->saveEntity($contact);

        $this->assertCount(1, $this->events);
    }

    public function testSavingLoadedContactAfterRepositoryUpdateDispatchesOneEvent(): void
    {
        $contact    = $this->createContact(new \DateTime('2026-01-01 10:00:00', new \DateTimeZone('UTC')));
        $lastActive = new \DateTime('2026-02-01 12:00:00', new \DateTimeZone('UTC'));

        self::getContainer()->get(LeadRepository::class)->updateLastActive($contact->getId(), $lastActive);
        $contact->setLastActive(clone $lastActive);
        self::getContainer()->get(LeadModel::class)->saveEntity($contact);

        $this->assertCount(1, $this->events);
    }

    public function testSavingContactWithPendingChangeAfterRepositoryUpdateDispatchesOneEvent(): void
    {
        $contact    = $this->createContact(new \DateTime('2026-01-01 10:00:00', new \DateTimeZone('UTC')));
        $lastActive = new \DateTime('2026-02-01 12:00:00', new \DateTimeZone('UTC'));

        $contact->setLastActive(clone $lastActive);
        self::getContainer()->get(LeadRepository::class)->updateLastActive($contact->getId(), $lastActive);
        self::getContainer()->get(LeadModel::class)->saveEntity($contact);

        $this->assertCount(1, $this->events);
    }

    public function testSavingContactAfterRepositoryUpdateKeepsNewerStoredValue(): void
    {
        $contact = $this->createContact(new \DateTime('2026-01-01 10:00:00', new \DateTimeZone('UTC')));

        self::getContainer()->get(LeadRepository::class)->updateLastActive($contact->getId(), new \DateTime('2026-02-01 12:00:00', new \DateTimeZone('UTC')));
        $this->em->getConnection()->executeStatement(
            'UPDATE '.MAUTIC_TABLE_PREFIX."leads SET last_active = '2026-02-01 12:05:00' WHERE id = ?",
            [$contact->getId()]
        );
        $contact->setFirstname('Changed');
        self::getContainer()->get(LeadModel::class)->saveEntity($contact);

        $this->assertSame(
            '2026-02-01 12:05:00',
            $this->em->getConnection()->fetchOne('SELECT last_active FROM '.MAUTIC_TABLE_PREFIX.'leads WHERE id = ?', [$contact->getId()])
        );
    }

    public function testSavingContactWithChangedLastActiveDispatchesEvent(): void
    {
        $contact = $this->createContact(null);

        $lastActive = new \DateTime('2026-03-01 08:00:00', new \DateTimeZone('UTC'));
        $contact->setLastActive($lastActive);
        self::getContainer()->get(LeadModel::class)->saveEntity($contact);

        $this->assertCount(1, $this->events);
        $this->assertSame($contact->getId(), $this->events[0]->getContactId());
        $this->assertSame('2026-03-01 08:00:00', $this->events[0]->getLastActive()->format('Y-m-d H:i:s'));
        $this->assertNotInstanceOf(\DateTimeInterface::class, $this->events[0]->getPreviousLastActive());
    }

    public function testSavingContactWithStringLastActiveDispatchesEvent(): void
    {
        $contact = $this->createContact(new \DateTime('2026-01-01 10:00:00', new \DateTimeZone('UTC')));

        $contact->setLastActive('2026-03-01 08:00:00');
        self::getContainer()->get(LeadModel::class)->saveEntity($contact);

        $this->assertCount(1, $this->events);
        $this->assertSame('2026-03-01 08:00:00', $this->events[0]->getLastActive()->format('Y-m-d H:i:s'));
        $this->assertSame('2026-01-01 10:00:00', $this->events[0]->getPreviousLastActive()?->format('Y-m-d H:i:s'));
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
