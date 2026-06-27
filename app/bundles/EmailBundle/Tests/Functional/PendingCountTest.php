<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListLead;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

final class PendingCountTest extends MauticMysqlTestCase
{
    /**
     * There was an issue that if there is a lead_id = null in the email_stats associated with an email
     * then the pending count was always 0 even if there are contacts waiting for sent.
     */
    public function testPendingCountWithDeletedContactsInEmailStats(): void
    {
        $contact = new Lead();
        $contact->setEmail('john@doe.email');

        $segment = new LeadList();
        $segment->setName('Segment A');
        $segment->setPublicName('Segment A');
        $segment->setAlias('segment-a');

        $segmentRef = new ListLead();
        $segmentRef->setLead($contact);
        $segmentRef->setList($segment);
        $segmentRef->setDateAdded(new \DateTime());

        $email = new Email();
        $email->setName('Email A');
        $email->setSubject('Email A Subject');
        $email->setEmailType('list');
        $email->addList($segment);

        $emailStat = new Stat();
        $emailStat->setEmail($email);
        $emailStat->setLead(null);
        $emailStat->setEmailAddress('deleted@contact.email');
        $emailStat->setDateSent(new \DateTime());

        $this->em->persist($segment);
        $this->em->persist($contact);
        $this->em->persist($segmentRef);
        $this->em->persist($email);
        $this->em->persist($emailStat);
        $this->em->flush();

        // The counts are loaded via ajax call after the email list page loads, so checking the ajax request instead of the HTML.
        $this->client->request(Request::METHOD_GET, '/s/ajax?action=email:getEmailCountStats', ['id' => $email->getId()]);

        Assert::assertSame(
            '{"id":'.$email->getId().',"pending":"1 Pending","queued":0,"sentCount":"0 Sent","readCount":"0 Read","readPercent":"0% Read"}',
            $this->client->getResponse()->getContent()
        );
    }

    public function testPendingCountWithDNCContactsInEmailStats(): void
    {
        $contact1 = new Lead();
        $contact1->setEmail('test-unsubscribe@doe.email');

        $doNotContact1 = new DoNotContact();
        $doNotContact1->setLead($contact1);
        $doNotContact1->setDateAdded(new \DateTime());
        $doNotContact1->setChannel('email');
        $doNotContact1->setReason(DoNotContact::UNSUBSCRIBED);
        $this->em->persist($doNotContact1);

        $contact2 = new Lead();
        $contact2->setEmail('test-bounced@doe.email');

        $doNotContact2 = new DoNotContact();
        $doNotContact2->setLead($contact2);
        $doNotContact2->setDateAdded(new \DateTime());
        $doNotContact2->setChannel('email');
        $doNotContact2->setReason(DoNotContact::BOUNCED);
        $this->em->persist($doNotContact2);

        $contact3 = new Lead();
        $contact3->setEmail('test-manual@doe.email');

        $doNotContact3 = new DoNotContact();
        $doNotContact3->setLead($contact3);
        $doNotContact3->setDateAdded(new \DateTime());
        $doNotContact3->setChannel('email');
        $doNotContact3->setReason(DoNotContact::MANUAL);
        $this->em->persist($doNotContact3);

        $segment = new LeadList();
        $segment->setName('Segment A');
        $segment->setPublicName('Segment A');
        $segment->setAlias('segment-a');

        $segmentRef1 = new ListLead();
        $segmentRef1->setLead($contact1);
        $segmentRef1->setList($segment);
        $segmentRef1->setDateAdded(new \DateTime());

        $segmentRef2 = new ListLead();
        $segmentRef2->setLead($contact2);
        $segmentRef2->setList($segment);
        $segmentRef2->setDateAdded(new \DateTime());

        $segmentRef3 = new ListLead();
        $segmentRef3->setLead($contact3);
        $segmentRef3->setList($segment);
        $segmentRef3->setDateAdded(new \DateTime());

        $email = new Email();
        $email->setName('Email A');
        $email->setSubject('Email A Subject');
        $email->setEmailType('list');
        $email->setSendToDnc(true);
        $email->addList($segment);

        $this->em->persist($segment);
        $this->em->persist($contact1);
        $this->em->persist($contact2);
        $this->em->persist($contact3);
        $this->em->persist($doNotContact1);
        $this->em->persist($doNotContact2);
        $this->em->persist($doNotContact3);
        $this->em->persist($segmentRef1);
        $this->em->persist($segmentRef2);
        $this->em->persist($segmentRef3);
        $this->em->persist($email);
        $this->em->flush();

        // The counts are loaded via ajax call after the email list page loads, so checking the ajax request instead of the HTML.
        $this->client->request(Request::METHOD_GET, '/s/ajax?action=email:getEmailCountStats', ['id' => $email->getId()]);

        Assert::assertSame(
            '{"id":'.$email->getId().',"pending":"2 Pending","queued":0,"sentCount":"0 Sent","readCount":"0 Read","readPercent":"0% Read"}',
            $this->client->getResponse()->getContent()
        );
    }
}
