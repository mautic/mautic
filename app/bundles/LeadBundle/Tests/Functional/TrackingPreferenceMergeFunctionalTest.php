<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Deduplicate\ContactMerger;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadDevice;
use Mautic\LeadBundle\Entity\TrackingPreference;
use Mautic\LeadBundle\Helper\ContactTrackingPreferenceHelper;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Merging two contacts keeps a choice to turn tracking off from either of them.
 */
final class TrackingPreferenceMergeFunctionalTest extends MauticMysqlTestCase
{
    /**
     * @return iterable<string, array{?bool, ?bool, bool, bool}>
     */
    public static function choiceProvider(): iterable
    {
        // winner's choice, loser's choice, winner allows tracking after the merge, winner has a recorded choice
        yield 'loser turned tracking off' => [null, false, false, true];
        yield 'winner turned tracking off' => [false, true, false, true];
        yield 'both turned tracking off' => [false, false, false, true];
        yield 'loser allows tracking' => [null, true, true, false];
        yield 'neither chose' => [null, null, true, false];
    }

    #[DataProvider('choiceProvider')]
    public function testMergedContactKeepsATurnedOffChoice(?bool $winnerChoice, ?bool $loserChoice, bool $allowsAfterMerge, bool $hasChoice): void
    {
        $helper = self::getContainer()->get(ContactTrackingPreferenceHelper::class);

        $winner = $this->createContact('winner@example.com');
        $loser  = $this->createContact('loser@example.com');

        if (null !== $winnerChoice) {
            $helper->setActivityTracking($winner, $winnerChoice, 'test');
        }
        if (null !== $loserChoice) {
            $helper->setActivityTracking($loser, $loserChoice, 'test');
        }

        $winnerId = $winner->getId();
        self::getContainer()->get(ContactMerger::class)->merge($winner, $loser);
        $this->em->clear();

        $winner = $this->em->find(Lead::class, $winnerId);
        $this->assertInstanceOf(Lead::class, $winner);
        $this->assertSame($allowsAfterMerge, $helper->allowsActivityTracking($winner));
        $this->assertSame($hasChoice, $this->em->getRepository(TrackingPreference::class)->count(['lead' => $winnerId]) > 0);
    }

    /**
     * @return iterable<string, array{bool, bool}>
     */
    public static function turnedOffChoiceProvider(): iterable
    {
        yield 'winner turned tracking off' => [true, false];
        yield 'loser turned tracking off' => [false, true];
    }

    /**
     * A device the merged contact takes over would otherwise still recognise a contact who turned tracking off.
     */
    #[DataProvider('turnedOffChoiceProvider')]
    public function testDevicesOfAMergedContactWhoTurnedTrackingOffAreForgotten(bool $winnerTurnedOff, bool $loserTurnedOff): void
    {
        $helper = self::getContainer()->get(ContactTrackingPreferenceHelper::class);

        $winner = $this->createContact('winner@example.com');
        $loser  = $this->createContact('loser@example.com');

        if ($winnerTurnedOff) {
            $helper->setActivityTracking($winner, false, 'test');
        }
        if ($loserTurnedOff) {
            $helper->setActivityTracking($loser, false, 'test');
        }

        // Tracked after the choice, as a device of a contact identified again is
        $this->createDevice($winner, 'mergewinnerdevice000001');
        $this->createDevice($loser, 'mergeloserdevice0000001');

        $winnerId = $winner->getId();
        self::getContainer()->get(ContactMerger::class)->merge($winner, $loser);
        $this->em->clear();

        $devices = $this->em->getRepository(LeadDevice::class)->findBy(['lead' => $winnerId]);
        $this->assertCount(2, $devices);
        foreach ($devices as $device) {
            $this->assertNull($device->getTrackingId());
        }
    }

    public function testDevicesOfAMergedContactWhoAllowsTrackingAreKept(): void
    {
        $winner = $this->createContact('winner@example.com');
        $loser  = $this->createContact('loser@example.com');
        $this->createDevice($winner, 'mergewinnerdevice000001');
        $this->createDevice($loser, 'mergeloserdevice0000001');

        $winnerId = $winner->getId();
        self::getContainer()->get(ContactMerger::class)->merge($winner, $loser);
        $this->em->clear();

        $trackingIds = array_map(static fn (LeadDevice $device): ?string => $device->getTrackingId(), $this->em->getRepository(LeadDevice::class)->findBy(['lead' => $winnerId]));
        sort($trackingIds);
        $this->assertSame(['mergeloserdevice0000001', 'mergewinnerdevice000001'], $trackingIds);
    }

    private function createDevice(Lead $contact, string $trackingId): void
    {
        $device = new LeadDevice();
        $device->setLead($contact);
        $device->setTrackingId($trackingId);
        $device->setDateAdded(new \DateTime());
        $device->setDevice('desktop');
        $this->em->persist($device);
        $this->em->flush();
    }

    private function createContact(string $email): Lead
    {
        $contact = new Lead();
        $contact->setEmail($email);
        $this->em->persist($contact);
        $this->em->flush();

        return $contact;
    }
}
