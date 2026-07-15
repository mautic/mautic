<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Entity;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\DoNotContactRepository;
use Mautic\LeadBundle\Entity\Lead;

final class DoNotContactRepositoryFunctionalTest extends MauticMysqlTestCase
{
    public function testGetChannelList(): void
    {
        $john = $this->createContact('Company A');
        $jane = $this->createContact('Company B');
        $josh = $this->createContact('Company B');

        $this->createDnc('email', $josh, DoNotContact::IS_CONTACTABLE);
        $this->createDnc('email', $john, DoNotContact::UNSUBSCRIBED);
        $this->createDnc('sms', $john, DoNotContact::BOUNCED);
        $this->createDnc('sms', $jane, DoNotContact::MANUAL);

        $this->em->flush();

        $repository = $this->em->getRepository(DoNotContact::class);
        $this->assertInstanceOf(DoNotContactRepository::class, $repository);

        $allDncRecords = $repository->getChannelList(null);
        $allSmsRecords = $repository->getChannelList('sms');

        $this->assertCount(3, $allDncRecords, 'Get all records for all channels (dangerous, do not use, there is no limit. One would expect this to return all 4 records, but they are grouped by contact ID.');
        $this->assertCount(2, $allSmsRecords, 'Get all records for sms channel (dangerous, do not use, there is no limit.');
        $this->assertCount(0, $repository->getChannelList('sms', []), 'Get all records for sms channel where the user filtered for a contact that do not exist. It must return an empty array. Not all DNC records.');
        $this->assertCount(1, $repository->getChannelList('sms', [$john->getId()]));
        $this->assertCount(2, $repository->getChannelList('sms', [$john->getId(), $jane->getId(), $josh->getId()]));
        $this->assertSame(['email' => (string) DoNotContact::IS_CONTACTABLE], $allDncRecords[$josh->getId()]);
        $this->assertSame(['email' => (string) DoNotContact::UNSUBSCRIBED, 'sms' => (string) DoNotContact::BOUNCED], $allDncRecords[$john->getId()]);
        $this->assertSame(['sms' => (string) DoNotContact::MANUAL], $allDncRecords[$jane->getId()]);
        $this->assertSame((string) DoNotContact::BOUNCED, $allSmsRecords[$john->getId()]);
        $this->assertSame((string) DoNotContact::MANUAL, $allSmsRecords[$jane->getId()]);
    }

    public function createDnc(string $channel, Lead $contact, int $reason): DoNotContact
    {
        $dnc = new DoNotContact();
        $dnc->setChannel($channel);
        $dnc->setLead($contact);
        $dnc->setReason($reason);
        $dnc->setDateAdded(new \DateTime());
        $this->em->persist($dnc);

        return $dnc;
    }

    private function createContact(string $firstName): Lead
    {
        $lead = new Lead();
        $lead->setFirstname($firstName);
        $this->em->persist($lead);

        return $lead;
    }
}
