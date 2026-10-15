<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Tests\Functional;

use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;
use Mautic\ChannelBundle\Entity\MessageQueue;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListLead;
use Mautic\SmsBundle\Broadcast\BroadcastQuery;
use Mautic\SmsBundle\Collection\RecipientCollection;
use Mautic\SmsBundle\Entity\Sms;
use Mautic\SmsBundle\Entity\Stat;
use Mautic\SmsBundle\Model\SmsModel;
use Mautic\SmsBundle\Sms\TransportChain;

final class BroadcastQueryFunctionalTest extends MauticMysqlTestCase
{
    use CreateEntitiesTrait;

    public function testPendingContactsAndCountUseTheSameEligibilityPolicy(): void
    {
        $firstSegment  = $this->createSegment('sms-broadcast-first');
        $secondSegment = $this->createSegment('sms-broadcast-second');
        $sms           = $this->createBroadcastSms([$firstSegment, $secondSegment]);

        $mobileContact       = $this->createContact('+41790000001', null);
        $phoneContact        = $this->createContact(null, '+41790000002');
        $phoneFallback       = $this->createContact('', '+41790000003');
        $multiSegmentContact = $this->createContact('+41790000004', null);
        $sentQueueContact    = $this->createContact('+41790000005', null);
        $emptyContact        = $this->createContact('', '');
        $emptyMobileContact  = $this->createContact('', null);
        $emptyPhoneContact   = $this->createContact(null, '');
        $nullContact         = $this->createContact(null, null);
        $dncContact          = $this->createContact('+41790000010', null);
        $previouslySent      = $this->createContact('+41790000011', null);
        $pendingQueueContact = $this->createContact('+41790000012', null);
        $removedContact      = $this->createContact('+41790000013', null);

        foreach ([
            $mobileContact,
            $phoneContact,
            $phoneFallback,
            $sentQueueContact,
            $emptyContact,
            $emptyMobileContact,
            $emptyPhoneContact,
            $nullContact,
            $dncContact,
            $previouslySent,
            $pendingQueueContact,
        ] as $contact) {
            $this->addContactToSegment($contact, $firstSegment);
        }
        $this->addContactToSegment($multiSegmentContact, $firstSegment);
        $this->addContactToSegment($multiSegmentContact, $secondSegment);
        $this->addContactToSegment($removedContact, $firstSegment, true);

        $this->em->flush();

        $this->createDnc($dncContact);
        $this->createStat($sms, $previouslySent);
        $this->createQueueEntry($sms, $pendingQueueContact, MessageQueue::STATUS_PENDING);
        $this->createQueueEntry($sms, $sentQueueContact, MessageQueue::STATUS_SENT);
        $this->em->flush();

        $pendingContacts = $this->getBroadcastQuery()->getPendingContacts($sms, new ContactLimiter(100));
        $actualIds       = array_map(intval(...), array_column($pendingContacts, 'id'));
        $expectedIds     = [
            $mobileContact->getId(),
            $phoneContact->getId(),
            $phoneFallback->getId(),
            $multiSegmentContact->getId(),
            $sentQueueContact->getId(),
        ];
        sort($expectedIds);

        $this->assertSame($expectedIds, $actualIds);
        $this->assertSame($actualIds, array_values(array_unique($actualIds)));
        $this->assertSame(count($expectedIds), $this->getBroadcastQuery()->getPendingCount($sms));

        $multiSegmentRow = array_values(array_filter(
            $pendingContacts,
            fn (array $row): bool => $multiSegmentContact->getId() === (int) $row['id'],
        ));
        $this->assertCount(1, $multiSegmentRow);
        $this->assertSame(
            min($firstSegment->getId(), $secondSegment->getId()),
            (int) $multiSegmentRow[0]['listId'],
        );
    }

    public function testScheduleModeControlsTheSegmentMembershipCutoff(): void
    {
        $firstSegment  = $this->createSegment('sms-snapshot-first');
        $secondSegment = $this->createSegment('sms-snapshot-second');
        $sms           = $this->createBroadcastSms([$firstSegment, $secondSegment]);
        $publishUp     = new \DateTime((new \DateTime('-1 hour'))->format('Y-m-d H:i:s'));

        $beforeStart = $this->createContact('+41790000601', null);
        $atStart     = $this->createContact('+41790000602', null);
        $afterStart  = $this->createContact('+41790000603', null);
        $multiSegmentContact = $this->createContact('+41790000604', null);

        $this->addContactToSegment($beforeStart, $firstSegment, false, (clone $publishUp)->modify('-1 second'));
        $this->addContactToSegment($atStart, $firstSegment, false, clone $publishUp);
        $this->addContactToSegment($afterStart, $firstSegment, false, (clone $publishUp)->modify('+1 second'));
        // Only the membership in the second segment is inside the one-time snapshot.
        $this->addContactToSegment($multiSegmentContact, $firstSegment, false, (clone $publishUp)->modify('+1 second'));
        $this->addContactToSegment($multiSegmentContact, $secondSegment, false, (clone $publishUp)->modify('-1 second'));

        $sms->setPublishUp($publishUp);
        $sms->setContinueSending(false);
        $this->em->flush();

        $oneTimeContacts = $this->getBroadcastQuery()->getPendingContacts($sms, new ContactLimiter(100));
        $oneTimeIds      = $this->getIds($oneTimeContacts);
        $expectedOneTimeIds = [
            $beforeStart->getId(),
            $atStart->getId(),
            $multiSegmentContact->getId(),
        ];
        sort($expectedOneTimeIds);

        $this->assertSame($expectedOneTimeIds, $oneTimeIds);
        $this->assertSame($oneTimeIds, array_values(array_unique($oneTimeIds)));
        $this->assertSame(count($oneTimeIds), $this->getBroadcastQuery()->getPendingCount($sms));

        $multiSegmentRows = array_values(array_filter(
            $oneTimeContacts,
            fn (array $row): bool => $multiSegmentContact->getId() === (int) $row['id'],
        ));
        $this->assertCount(1, $multiSegmentRows);
        $this->assertSame($secondSegment->getId(), (int) $multiSegmentRows[0]['listId']);

        $sms->setContinueSending(true);

        $continuingIds = $this->getIds(
            $this->getBroadcastQuery()->getPendingContacts($sms, new ContactLimiter(100)),
        );
        $expectedContinuingIds = [
            $beforeStart->getId(),
            $atStart->getId(),
            $afterStart->getId(),
            $multiSegmentContact->getId(),
        ];
        sort($expectedContinuingIds);

        $this->assertSame($expectedContinuingIds, $continuingIds);
        $this->assertSame($continuingIds, array_values(array_unique($continuingIds)));
        $this->assertSame(count($continuingIds), $this->getBroadcastQuery()->getPendingCount($sms));
    }

    public function testPendingContactsCanRecheckABoundedListOfIds(): void
    {
        $segment = $this->createSegment('sms-bounded-ids');
        $sms     = $this->createBroadcastSms([$segment]);

        $firstEligible  = $this->createContact('+41790000101', null);
        $secondEligible = $this->createContact(null, '+41790000102');
        $emptyContact   = $this->createContact('', '');
        $dncContact     = $this->createContact('+41790000104', null);
        $outsideSegment = $this->createContact('+41790000105', null);

        foreach ([$firstEligible, $secondEligible, $emptyContact, $dncContact] as $contact) {
            $this->addContactToSegment($contact, $segment);
        }

        $this->em->flush();
        $this->createDnc($dncContact);
        $this->em->flush();

        $requestedIds = [
            $outsideSegment->getId(),
            $secondEligible->getId(),
            $dncContact->getId(),
            $firstEligible->getId(),
            $emptyContact->getId(),
            $secondEligible->getId(),
        ];
        $pendingContacts = $this->getBroadcastQuery()->getPendingContactsForContactIds($sms, $requestedIds);
        $actualIds       = array_map(intval(...), array_column($pendingContacts, 'id'));
        $expectedIds     = [$firstEligible->getId(), $secondEligible->getId()];
        sort($expectedIds);

        $this->assertSame($expectedIds, $actualIds);
        $this->assertSame([], $this->getBroadcastQuery()->getPendingContactsForContactIds($sms, []));

        $limiter = new ContactLimiter(100, contactIdList: $requestedIds);
        $this->assertSame(count($pendingContacts), $this->getBroadcastQuery()->getPendingCount($sms, $limiter));
    }

    public function testPendingContactsAdvanceByContactIdAcrossBatches(): void
    {
        $firstSegment  = $this->createSegment('sms-cursor-first');
        $secondSegment = $this->createSegment('sms-cursor-second');
        $sms           = $this->createBroadcastSms([$firstSegment, $secondSegment]);
        $contacts      = [];

        for ($i = 0; $i < 5; ++$i) {
            $contact   = $this->createContact(sprintf('+417900002%02d', $i), null);
            $contacts[] = $contact;
            $this->addContactToSegment($contact, $firstSegment);

            if (0 === $i) {
                $this->addContactToSegment($contact, $secondSegment);
            }

            // Leave gaps between eligible contact IDs to exercise ID-based rather than offset pagination.
            $this->createContact(sprintf('+417900003%02d', $i), null);
        }

        $this->em->flush();

        $limiter   = new ContactLimiter(2);
        $actualIds = [];
        $batchSizes = [];

        while (true) {
            $batch = $this->getBroadcastQuery()->getPendingContacts($sms, $limiter);
            if ([] === $batch) {
                break;
            }

            $batchSizes[] = count($batch);
            foreach ($batch as $row) {
                $actualIds[] = (int) $row['id'];
            }

            $lastRow = end($batch);
            $limiter->setBatchMinContactId(((int) $lastRow['id']) + 1);
        }

        $expectedIds = array_map(static fn (Lead $contact): int => $contact->getId(), $contacts);
        sort($expectedIds);

        $this->assertSame([2, 2, 1], $batchSizes);
        $this->assertSame($expectedIds, $actualIds);
        $this->assertSame($actualIds, array_values(array_unique($actualIds)));
        $this->assertSame([], $this->getBroadcastQuery()->getPendingContacts($sms, new ContactLimiter(100), 0));
        $this->assertCount(1, $this->getBroadcastQuery()->getPendingContacts($sms, new ContactLimiter(100), 1));
    }

    public function testThreadPartitionsAreDisjointAndCoverAllPendingContacts(): void
    {
        $firstSegment  = $this->createSegment('sms-thread-first');
        $secondSegment = $this->createSegment('sms-thread-second');
        $sms           = $this->createBroadcastSms([$firstSegment, $secondSegment]);
        $contacts      = [];

        for ($i = 0; $i < 8; ++$i) {
            $contact   = $this->createContact(sprintf('+417900004%02d', $i), null);
            $contacts[] = $contact;
            $this->addContactToSegment($contact, $firstSegment);

            if (0 === $i % 3) {
                $this->addContactToSegment($contact, $secondSegment);
            }
        }

        $this->em->flush();

        $firstThreadLimiter  = new ContactLimiter(100, threadId: 1, maxThreads: 2);
        $secondThreadLimiter = new ContactLimiter(100, threadId: 2, maxThreads: 2);
        $firstThreadIds      = $this->getIds($this->getBroadcastQuery()->getPendingContacts($sms, $firstThreadLimiter));
        $secondThreadIds     = $this->getIds($this->getBroadcastQuery()->getPendingContacts($sms, $secondThreadLimiter));
        $allIds              = array_map(static fn (Lead $contact): int => $contact->getId(), $contacts);
        sort($allIds);

        $this->assertSame([], array_values(array_intersect($firstThreadIds, $secondThreadIds)));

        $partitionedIds = array_merge($firstThreadIds, $secondThreadIds);
        sort($partitionedIds);
        $this->assertSame($allIds, $partitionedIds);
        $this->assertSame(count($firstThreadIds), $this->getBroadcastQuery()->getPendingCount($sms, $firstThreadLimiter));
        $this->assertSame(count($secondThreadIds), $this->getBroadcastQuery()->getPendingCount($sms, $secondThreadLimiter));

        foreach ($firstThreadIds as $contactId) {
            $this->assertSame(0, $contactId % 2);
        }
        foreach ($secondThreadIds as $contactId) {
            $this->assertSame(1, $contactId % 2);
        }
    }

    public function testPendingQueryOrdersByTheSelectedContactId(): void
    {
        $sms = $this->createBroadcastSms([]);
        $this->em->flush();

        $sql = $this->getBroadcastQuery()->getBasicQuery($sms)->getSQL();

        $this->assertStringEndsWith('ORDER BY l.id ASC', $sql);
    }

    public function testSuccessfulTranslatedSendIsNotReselectedForTheParentBroadcast(): void
    {
        $segment     = $this->createSegment('sms-translated-send');
        $sms         = $this->createBroadcastSms([$segment]);
        $translated  = $this->createAnSms('French broadcast SMS', 'Bonjour', true, 'fr_FR');
        $contact     = $this->createContact('+41790000501', null);
        $translated->setTranslationParent($sms);
        $this->em->persist($translated);
        $this->addContactToSegment($contact, $segment);
        $this->em->flush();

        $smsId        = $sms->getId();
        $translatedId = $translated->getId();
        $contactId    = $contact->getId();
        $this->em->clear();

        $sms     = $this->em->find(Sms::class, $smsId);
        $contact = $this->em->find(Lead::class, $contactId);
        $this->assertInstanceOf(Sms::class, $sms);
        $this->assertInstanceOf(Lead::class, $contact);
        $contact->addUpdatedField('preferred_locale', 'fr_FR');
        $this->em->flush();
        $this->assertSame([$contactId], $this->getIds($this->getBroadcastQuery()->getPendingContacts($sms, new ContactLimiter(100))));

        $transport = $this->createMock(TransportChain::class);
        $transport->expects($this->once())
            ->method('sendBatchSms')
            ->with($this->isInstanceOf(RecipientCollection::class), 'Bonjour')
            ->willReturnCallback(static function (RecipientCollection $recipients): RecipientCollection {
                foreach ($recipients as $recipient) {
                    $recipient->setResult(true);
                }

                return $recipients;
            });
        self::getContainer()->set('mautic.sms.transport_chain', $transport);

        /** @var SmsModel $smsModel */
        $smsModel = self::getContainer()->get(SmsModel::class);
        $results  = $smsModel->sendSms($sms, $contact);

        $this->assertTrue($results[$contactId]['sent']);
        $stats = $smsModel->getStatRepository()->findBy(['lead' => $contactId]);
        $this->assertCount(1, $stats);
        $this->assertSame($translatedId, $stats[0]->getSms()?->getId());
        $this->assertSame([], $this->getBroadcastQuery()->getPendingContacts($sms, new ContactLimiter(100)));
        $this->assertSame(0, $this->getBroadcastQuery()->getPendingCount($sms));
    }

    public function testUnusableNumberFailureIsNotReselected(): void
    {
        $segment = $this->createSegment('sms-unusable-number');
        $sms     = $this->createBroadcastSms([$segment]);
        $contact = $this->createContact("\t", null);
        $this->addContactToSegment($contact, $segment);
        $this->em->flush();

        $contactId = $contact->getId();
        $this->assertSame([$contactId], $this->getIds($this->getBroadcastQuery()->getPendingContacts($sms, new ContactLimiter(100))));

        $transport = $this->createMock(TransportChain::class);
        $transport->expects($this->never())->method('sendBatchSms');
        self::getContainer()->set('mautic.sms.transport_chain', $transport);

        /** @var SmsModel $smsModel */
        $smsModel = self::getContainer()->get(SmsModel::class);
        $results  = $smsModel->sendSms($sms, $contact);

        $this->assertSame('mautic.sms.campaign.failed.missing_number', $results[$contactId]['status']);
        $stats = $smsModel->getStatRepository()->findBy(['sms' => $sms->getId(), 'lead' => $contactId]);
        $this->assertCount(1, $stats);
        $this->assertTrue($stats[0]->isFailed());
        $this->assertSame([], $this->getBroadcastQuery()->getPendingContacts($sms, new ContactLimiter(100)));
        $this->assertSame(0, $this->getBroadcastQuery()->getPendingCount($sms));
    }

    private function getBroadcastQuery(): BroadcastQuery
    {
        return self::getContainer()->get(BroadcastQuery::class);
    }

    /**
     * @param LeadList[] $segments
     */
    private function createBroadcastSms(array $segments): Sms
    {
        $sms = $this->createAnSms('Broadcast SMS', 'Hello');
        $sms->setSmsType('list');
        foreach ($segments as $segment) {
            $sms->addList($segment);
        }
        $this->em->persist($sms);

        return $sms;
    }

    private function createSegment(string $alias): LeadList
    {
        $segment = new LeadList();
        $segment->setName($alias);
        $segment->setPublicName($alias);
        $segment->setAlias($alias);
        $segment->setIsPublished(true);
        $this->em->persist($segment);

        return $segment;
    }

    private function createContact(?string $mobile, ?string $phone): Lead
    {
        $contact = new Lead();
        $contact->setMobile($mobile);
        $contact->setPhone($phone);
        $this->em->persist($contact);

        return $contact;
    }

    private function addContactToSegment(
        Lead $contact,
        LeadList $segment,
        bool $manuallyRemoved = false,
        ?\DateTime $dateAdded = null,
    ): void {
        $listLead = new ListLead();
        $listLead->setLead($contact);
        $listLead->setList($segment);
        $listLead->setDateAdded($dateAdded ?? new \DateTime());
        $listLead->setManuallyRemoved($manuallyRemoved);
        $this->em->persist($listLead);
    }

    private function createDnc(Lead $contact): void
    {
        $dnc = new DoNotContact();
        $dnc->setLead($contact);
        $dnc->setDateAdded(new \DateTime());
        $dnc->setReason(DoNotContact::MANUAL);
        $dnc->setChannel('sms');
        $this->em->persist($dnc);
    }

    private function createStat(Sms $sms, Lead $contact): void
    {
        $stat = new Stat();
        $stat->setSms($sms);
        $stat->setLead($contact);
        $stat->setDateSent(new \DateTime());
        $this->em->persist($stat);
    }

    private function createQueueEntry(Sms $sms, Lead $contact, string $status): void
    {
        $queueEntry = new MessageQueue();
        $queueEntry->setLead($contact);
        $queueEntry->setChannel('sms');
        $queueEntry->setChannelId($sms->getId());
        $queueEntry->setStatus($status);
        $this->em->persist($queueEntry);
    }

    /**
     * @param array<int, array<string, mixed>> $contacts
     *
     * @return int[]
     */
    private function getIds(array $contacts): array
    {
        return array_map(intval(...), array_column($contacts, 'id'));
    }
}
