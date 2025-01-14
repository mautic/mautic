<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Functional;

use DateTime;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
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
        $segmentRef->setDateAdded(new DateTime());

        $email = new Email();
        $email->setName('Email A');
        $email->setSubject('Email A Subject');
        $email->setEmailType('list');
        $email->addList($segment);

        $emailStat = new Stat();
        $emailStat->setEmail($email);
        $emailStat->setLead(null);
        $emailStat->setEmailAddress('deleted@contact.email');
        $emailStat->setDateSent(new DateTime());

        $this->em->persist($segment);
        $this->em->persist($contact);
        $this->em->persist($segmentRef);
        $this->em->persist($email);
        $this->em->persist($emailStat);
        $this->em->flush();

        // The counts are loaded via ajax call after the email list page loads, so checking the ajax request instead of the HTML.
        $this->client->request(Request::METHOD_POST, '/s/ajax?action=email:getEmailCountStats', ['id' => $email->getId()]);

        Assert::assertSame(
            '{"id":'.$email->getId().',"pending":"1 Pending","queued":0,"sentCount":"0 Sent","readCount":"0 Read","readPercent":"0% Read"}',
            $this->client->getResponse()->getContent()
        );
    }
}
