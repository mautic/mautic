<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Entity;

use DateTime;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\EmailRepository;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Assert;

class EmailRepositoryFunctionalTest extends MauticMysqlTestCase
{
    private EmailRepository $emailRepository;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var EmailRepository $repository */
        $repository = $this->em->getRepository(Email::class);

        $this->emailRepository = $repository;
    }

    public function testGetDoNotEmailListEmpty(): void
    {
        $result = $this->emailRepository->getDoNotEmailList();

        Assert::assertSame([], $result);
    }

    public function testGetDoNotEmailListNotEmpty(): void
    {
        $lead = new Lead();
        $lead->setEmail('name@domain.tld');
        $this->em->persist($lead);

        $doNotContact = new DoNotContact();
        $doNotContact->setLead($lead);
        $doNotContact->setDateAdded(new DateTime());
        $doNotContact->setChannel('email');
        $this->em->persist($doNotContact);

        $this->em->flush();

        // no $leadIds
        $result = $this->emailRepository->getDoNotEmailList();
        Assert::assertSame([$lead->getId() => $lead->getEmail()], $result);

        // matching $leadIds
        $result = $this->emailRepository->getDoNotEmailList([$lead->getId()]);
        Assert::assertSame([$lead->getId() => $lead->getEmail()], $result);

        // mismatching $leadIds
        $result = $this->emailRepository->getDoNotEmailList([-1]);
        Assert::assertSame([], $result);
    }

    public function testCheckDoNotEmailNonExistent(): void
    {
        $result = $this->emailRepository->checkDoNotEmail('name@domain.tld');

        Assert::assertFalse($result);
    }

    public function testCheckDoNotEmailExistent(): void
    {
        $lead = new Lead();
        $lead->setEmail('name@domain.tld');
        $this->em->persist($lead);

        $doNotContact = new DoNotContact();
        $doNotContact->setLead($lead);
        $doNotContact->setDateAdded(new DateTime());
        $doNotContact->setChannel('email');
        $doNotContact->setReason(1);
        $doNotContact->setComments('Some comment');
        $this->em->persist($doNotContact);

        $this->em->flush();

        $result = $this->emailRepository->checkDoNotEmail('name@domain.tld');

        Assert::assertSame([
            'id'           => (string) $doNotContact->getId(),
            'unsubscribed' => true,
            'bounced'      => false,
            'manual'       => false,
            'comments'     => $doNotContact->getComments(),
        ], $result);
    }
}
