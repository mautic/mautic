<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional\Model;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Model\DoNotContact as DoNotContactModel;
use Mautic\LeadBundle\Model\LeadModel;
use PHPUnit\Framework\Assert;

final class DoNotContactLogFunctionalTest extends MauticMysqlTestCase
{
    public function testUnsubscribeAndResubscribeAreLogged(): void
    {
        $contact = $this->createContact();

        $this->getDncModel()->addDncForContact($contact->getId(), 'email', DoNotContact::UNSUBSCRIBED, 'Clicked the link');

        $logs = $this->getLogs();
        Assert::assertCount(1, $logs);
        Assert::assertSame('unsubscribed', $logs[0]->getAction());
        Assert::assertSame('lead', $logs[0]->getBundle());
        Assert::assertSame($contact->getId(), $logs[0]->getLead()->getId());

        $dnc = $this->em->getRepository(DoNotContact::class)->findOneBy(['lead' => $contact]);
        Assert::assertSame($dnc->getId(), $logs[0]->getObjectId());
        Assert::assertSame(
            ['channel' => 'email', 'channel_id' => null, 'reason' => DoNotContact::UNSUBSCRIBED, 'comments' => 'Clicked the link'],
            $logs[0]->getProperties()
        );

        $dncId = $dnc->getId();
        $this->em->clear();

        Assert::assertTrue($this->getDncModel()->removeDncForContact($contact->getId(), 'email'));

        $logs = $this->getLogs();
        Assert::assertCount(2, $logs);
        Assert::assertSame('subscribed', $logs[1]->getAction());
        Assert::assertSame($dncId, $logs[1]->getObjectId());
        Assert::assertSame(
            ['channel' => 'email', 'channel_id' => null, 'reason' => DoNotContact::UNSUBSCRIBED, 'comments' => ''],
            $logs[1]->getProperties(),
            'The removed record\'s reason is stored.'
        );
    }

    public function testSavingTheContactAgainDoesNotLogTheSameDncTwice(): void
    {
        $contact = $this->createContact();

        $this->getDncModel()->addDncForContact($contact->getId(), 'email', DoNotContact::MANUAL, 'user');

        $contact = $this->em->getRepository(Lead::class)->find($contact->getId());
        $contact->setFirstname('Changed');
        $this->getLeadModel()->saveEntity($contact);
        $this->getLeadModel()->saveEntity($contact);

        Assert::assertCount(1, $this->getLogs());
    }

    public function testRemovingAbsentDncIsNotLogged(): void
    {
        $contact = $this->createContact();

        Assert::assertFalse($this->getDncModel()->removeDncForContact($contact->getId(), 'email'));
        Assert::assertCount(0, $this->getLogs());
    }

    private function createContact(): Lead
    {
        $contact = new Lead();
        $contact->setEmail('dnc-log@example.com');
        $this->em->persist($contact);
        $this->em->flush();

        return $contact;
    }

    /**
     * @return LeadEventLog[]
     */
    private function getLogs(): array
    {
        $this->em->clear();

        return $this->em->getRepository(LeadEventLog::class)->findBy(['object' => 'dnc'], ['id' => 'ASC']);
    }

    private function getDncModel(): DoNotContactModel
    {
        return static::getContainer()->get(DoNotContactModel::class);
    }

    private function getLeadModel(): LeadModel
    {
        return static::getContainer()->get(LeadModel::class);
    }
}
