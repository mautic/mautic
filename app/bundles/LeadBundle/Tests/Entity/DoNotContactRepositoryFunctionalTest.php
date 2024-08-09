<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Entity;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\DoNotContactRepository;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Assert;

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
        \assert($repository instanceof DoNotContactRepository);

        $allDncRecords = $repository->getChannelList(null);
        $allSmsRecords = $repository->getChannelList('sms');

        Assert::assertCount(3, $allDncRecords, 'Get all records for all channels (dangerous, do not use, there is no limit. One would expect this to return all 4 records, but they are grouped by contact ID.');
        Assert::assertCount(2, $allSmsRecords, 'Get all records for sms channel (dangerous, do not use, there is no limit.');
        Assert::assertCount(0, $repository->getChannelList('sms', []), 'Get all records for sms channel where the user filtered for a contact that do not exist. It must return an empty array. Not all DNC records.');
        Assert::assertCount(1, $repository->getChannelList('sms', [$john->getId()]));
        Assert::assertCount(2, $repository->getChannelList('sms', [$john->getId(), $jane->getId(), $josh->getId()]));
        Assert::assertSame(['email' => (string) DoNotContact::IS_CONTACTABLE], $allDncRecords[$josh->getId()]);
        Assert::assertSame(['email' => (string) DoNotContact::UNSUBSCRIBED, 'sms' => (string) DoNotContact::BOUNCED], $allDncRecords[$john->getId()]);
        Assert::assertSame(['sms' => (string) DoNotContact::MANUAL], $allDncRecords[$jane->getId()]);
        Assert::assertSame((string) DoNotContact::BOUNCED, $allSmsRecords[$john->getId()]);
        Assert::assertSame((string) DoNotContact::MANUAL, $allSmsRecords[$jane->getId()]);
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
