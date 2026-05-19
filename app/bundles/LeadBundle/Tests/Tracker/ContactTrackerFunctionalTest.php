<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Tracker;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadDevice;
use Mautic\LeadBundle\Tracker\ContactTracker;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class ContactTrackerFunctionalTest extends MauticMysqlTestCase
{
    private ContactTracker $contactTracker;
    private RequestStack $requestStack;

    protected function setUp(): void
    {
        parent::setUp();

        $this->contactTracker = static::getContainer()->get(ContactTracker::class);
        $this->requestStack   = static::getContainer()->get(RequestStack::class);
    }

    public function testDeletedContactTrackedDeviceDoesNotThrow(): void
    {
        static::getContainer()->get(TokenStorageInterface::class)->setToken(null);
        $this->contactTracker->setUseSystemContact(false);

        $contact = $this->createContact('deleted-contact@domain.tld');
        $device  = $this->createDevice($contact, 'track-deleted-contact');
        $this->em->flush();

        $contactId = $contact->getId();

        // Simulate the race condition: delete the lead row directly via DBAL, bypassing
        // Doctrine's ON DELETE CASCADE so the device record remains orphaned.
        // This replicates the window where a request has already loaded the lead_id
        // from the device table but the lead is deleted before it can be fetched.
        $this->em->getConnection()
            ->executeStatement('DELETE FROM leads WHERE id = :id', ['id' => $contactId]);

        $this->em->clear();

        // A returning visitor whose cookie still references the deleted contact ID
        // should be treated as a new anonymous visitor instead of throwing EntityNotFoundException
        $result = $this->trackContactByDevice($device);

        Assert::assertNull($result, 'Expected null (new anonymous visitor) when tracked device references a deleted contact.');
    }

    public function testReset(): void
    {
        static::getContainer()->get(TokenStorageInterface::class)->setToken(null);
        $this->contactTracker->setUseSystemContact(false);

        $contactOne = $this->createContact('test-one@domain.tld');
        $deviceOne  = $this->createDevice($contactOne, 'track-me-one');
        $this->em->flush();

        Assert::assertSame($contactOne, $this->trackContactByDevice($deviceOne));

        $contactTwo = $this->createContact('test-two@domain.tld');
        $deviceTwo  = $this->createDevice($contactTwo, 'track-me-two');
        $this->em->flush();

        Assert::assertSame($contactTwo, $this->trackContactByDevice($deviceTwo));
    }

    private function createContact(string $email): Lead
    {
        $lead = new Lead();
        $lead->setEmail($email);
        $this->em->persist($lead);

        return $lead;
    }

    private function createDevice(Lead $leadOne, string $trackingId): LeadDevice
    {
        $device = new LeadDevice();
        $device->setDateAdded(new \DateTime());
        $device->setTrackingId($trackingId);
        $device->setLead($leadOne);
        $this->em->persist($device);

        return $device;
    }

    private function trackContactByDevice(LeadDevice $device): ?Lead
    {
        $request = new Request([
            'mautic_device_id' => $device->getTrackingId(),
        ], [], [], [], [], [
            'HTTP_CLIENT_IP'  => '124.56.35.14',
            'HTTP_USER_AGENT' => 'Functional tester',
        ]);

        $this->requestStack->pop();
        $this->requestStack->push($request);
        $this->contactTracker->reset();

        return $this->contactTracker->getContact();
    }
}
