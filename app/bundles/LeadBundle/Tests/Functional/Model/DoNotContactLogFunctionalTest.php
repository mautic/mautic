<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional\Model;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Model\DoNotContact as DoNotContactModel;
use Mautic\LeadBundle\Model\LeadModel;

final class DoNotContactLogFunctionalTest extends MauticMysqlTestCase
{
    public function testUnsubscribeAndResubscribeAreLogged(): void
    {
        $contact = $this->createContact();

        $this->getDncModel()->addDncForContact($contact->getId(), 'email', DoNotContact::UNSUBSCRIBED, 'Clicked the link');

        $logs = $this->getLogs();
        $this->assertCount(1, $logs);
        $this->assertSame('unsubscribed', $logs[0]->getAction());
        $this->assertSame('lead', $logs[0]->getBundle());
        $this->assertSame($contact->getId(), $logs[0]->getLead()->getId());

        $dnc = $this->em->getRepository(DoNotContact::class)->findOneBy(['lead' => $contact]);
        $this->assertInstanceOf(DoNotContact::class, $dnc);
        $this->assertSame($dnc->getId(), $logs[0]->getObjectId());
        $this->assertEquals(['channel' => 'email', 'channel_id' => null, 'reason' => DoNotContact::UNSUBSCRIBED, 'comments' => 'Clicked the link'], $logs[0]->getProperties());

        $dncId = $dnc->getId();
        $this->em->clear();

        $this->assertTrue($this->getDncModel()->removeDncForContact($contact->getId(), 'email'));

        $logs = $this->getLogs();
        $this->assertCount(2, $logs);
        $this->assertSame('subscribed', $logs[1]->getAction());
        $this->assertSame($dncId, $logs[1]->getObjectId());
        $this->assertEquals(['channel' => 'email', 'channel_id' => null, 'reason' => DoNotContact::UNSUBSCRIBED, 'comments' => ''], $logs[1]->getProperties(), 'The removed record\'s reason is stored.');
    }

    public function testSavingTheContactAgainDoesNotLogTheSameDncTwice(): void
    {
        $contact = $this->createContact();

        $this->getDncModel()->addDncForContact($contact->getId(), 'email', DoNotContact::MANUAL, 'user');

        $contact = $this->em->getRepository(Lead::class)->find($contact->getId());
        $this->assertInstanceOf(Lead::class, $contact);
        $contact->setFirstname('Changed');
        $this->getLeadModel()->saveEntity($contact);
        $this->getLeadModel()->saveEntity($contact);

        $this->assertCount(1, $this->getLogs());
    }

    public function testRemovingAbsentDncIsNotLogged(): void
    {
        $contact = $this->createContact();

        $this->assertFalse($this->getDncModel()->removeDncForContact($contact->getId(), 'email'));
        $this->assertCount(0, $this->getLogs());
    }

    public function testDncAddedAndRemovedBeforeSavingIsNotLogged(): void
    {
        $contact = $this->createContact();

        $this->getDncModel()->addDncForContact($contact->getId(), 'email', DoNotContact::MANUAL, 'user', false);
        $this->getDncModel()->removeDncForContact($contact->getId(), 'email', false);
        $this->getLeadModel()->saveEntity($this->getLeadModel()->getEntity($contact->getId()));

        $this->assertCount(0, $this->getLogs());
        $this->assertNotInstanceOf(DoNotContact::class, $this->em->getRepository(DoNotContact::class)->findOneBy(['lead' => $contact->getId()]));
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
        return self::getContainer()->get(DoNotContactModel::class);
    }

    private function getLeadModel(): LeadModel
    {
        return self::getContainer()->get(LeadModel::class);
    }
}
