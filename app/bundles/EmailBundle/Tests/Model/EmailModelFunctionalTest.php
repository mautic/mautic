<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Model;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListLead;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\HttpFoundation\Request;

class EmailModelFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = true;

    protected function beforeBeginTransaction(): void
    {
        $this->resetAutoincrement(['leads']);
    }

    public function testSendEmailToListsInThreads(): void
    {
        $contacts = $this->generateContacts(23);
        $segment  = $this->createSegment();
        $this->addContactsToSegment($contacts, $segment);
        $email = $this->createEmail($segment);

        $emailModel                                             =  self::$container->get('mautic.email.model.email');

        [$sentCount] = $emailModel->sendEmailToLists($email, [$segment], null, null, null, null, null, 3, 1);
        $this->assertEquals($sentCount, 7);
        [$sentCount] = $emailModel->sendEmailToLists($email, [$segment], null, null, null, null, null, 3, 2);
        $this->assertEquals($sentCount, 8);
        [$sentCount] = $emailModel->sendEmailToLists($email, [$segment], null, null, null, null, null, 3, 3);
        $this->assertEquals($sentCount, 8);
    }

    /**
     * @return Lead[]
     */
    private function generateContacts(int $howMany): array
    {
        $contacts = [];

        for ($i = 0; $i < $howMany; ++$i) {
            $contact = new Lead();
            $contact->setEmail("test{$i}@some.email");
            $contacts[] = $contact;
        }

        $contactModel = self::$container->get('mautic.lead.model.lead');
        \assert($contactModel instanceof LeadModel);
        $contactModel->saveEntities($contacts);

        return $contacts;
    }

    private function createSegment(): LeadList
    {
        $segment = new LeadList();
        $segment->setName('Segment A');
        $segment->setPublicName('Segment A');
        $segment->setAlias('segment-a');
        $this->em->persist($segment);
        $this->em->flush();

        return $segment;
    }

    /**
     * @param Lead[] $contacts
     */
    private function addContactsToSegment(array $contacts, LeadList $segment, ?\DateTime $dateAdded = null): void
    {
        foreach ($contacts as $contact) {
            $reference = new ListLead();
            $reference->setLead($contact);
            $reference->setList($segment);
            $reference->setDateAdded($dateAdded ?? new \DateTime());
            $this->em->persist($reference);
        }

        $this->em->flush();
    }

    private function createEmail(LeadList $segment): Email
    {
        $email = new Email();
        $email->setName('Email');
        $email->setSubject('Email Subject');
        $email->setCustomHtml('Email content');
        $email->setEmailType('list');
        $email->setPublishUp(new \DateTime('-1 day'));
        $email->setContinueSending(true);
        $email->setIsPublished(true);
        $email->addList($segment);
        $this->em->persist($email);
        $this->em->flush();

        return $email;
    }

    public function testSendEmailToLists(): void
    {
        $contacts = $this->generateContacts(10);
        $segment  = $this->createSegment();
        $this->addContactsToSegment($contacts, $segment);
        $email = $this->createEmail($segment);

        $emailModel                                             =  self::$container->get('mautic.email.model.email');
        [$sentCount, $failedCount, $failedRecipientsByList]     = $emailModel->sendEmailToLists($email, [$segment], 4, 2);
        $this->assertEquals($sentCount, 4);
        [$sentCount, $failedCount, $failedRecipientsByList] = $emailModel->sendEmailToLists($email, [$segment], 3, 2);
        $this->assertEquals($sentCount, 3);
        [$sentCount, $failedCount, $failedRecipientsByList] = $emailModel->sendEmailToLists($email, [$segment], 2);
        $this->assertEquals($sentCount, 2);
        [$sentCount, $failedCount, $failedRecipientsByList] = $emailModel->sendEmailToLists($email, [$segment], 4);
        $this->assertEquals($sentCount, 1);

        $email                                                  = $this->createEmail($segment);
        [$sentCount, $failedCount, $failedRecipientsByList]     = $emailModel->sendEmailToLists($email, [$segment]);
        $this->assertEquals($sentCount, 10);

        $email                                                  = $this->createEmail($segment);
        [$sentCount, $failedCount, $failedRecipientsByList]     = $emailModel->sendEmailToLists($email, [$segment], null, 2);
        $this->assertEquals($sentCount, 10);
    }

   public function testNotOverwriteChildrenTranslationEmailAfterSaveParent(): void
   {
       $segment        = new LeadList();
       $segmentName    = 'Test_segment';
       $segment->setName($segmentName);
       $segment->setPublicName($segmentName);
       $segment->setAlias($segmentName);
       $this->em->persist($segment);

       $emailName        = 'Test';
       $customHtmlParent = 'test EN';
       $parentEmail      = new Email();
       $parentEmail->setName($emailName);
       $parentEmail->setSubject($emailName);
       $parentEmail->setCustomHTML($customHtmlParent);
       $parentEmail->setEmailType('template');
       $parentEmail->setLanguage('en');
       $this->em->persist($parentEmail);

       $customHtmlChildren = 'test FR';
       $childrenEmail      = clone $parentEmail;
       $childrenEmail->setLanguage('fr');
       $childrenEmail->setCustomHTML($customHtmlChildren);
       $childrenEmail->setTranslationParent($parentEmail);
       $this->em->persist($parentEmail);

       $this->em->detach($segment);
       $this->em->detach($parentEmail);
       $this->em->detach($childrenEmail);

       /** @var EmailModel $emailModel */
       $emailModel = self::$container->get('mautic.email.model.email');
       $parentEmail->setName('Test change');
       $emailModel->saveEntity($parentEmail);

       self::assertSame($customHtmlParent, $parentEmail->getCustomHtml());
       self::assertSame($customHtmlChildren, $childrenEmail->getCustomHtml());
   }

    public function testScheduledSendEmailToLists(): void
    {
        $contacts       = array_chunk($this->generateContacts(23), 20);
        $contactsBefore = $contacts[0];
        $contactsAfter  = $contacts[1];

        $segment  = $this->createSegment();
        $this->addContactsToSegment($contactsBefore, $segment, new \DateTime('-2 days'));

        $emailWithContinueSending = $this->createEmail($segment);

        $emailWithoutContinueSending = $this->createEmail($segment);
        $emailWithoutContinueSending->setContinueSending(false);
        $this->em->persist($emailWithoutContinueSending);

        $this->em->flush();

        $emailModel                                             =  self::$container->get('mautic.email.model.email');
        \assert($emailModel instanceof EmailModel);

        // refresh pending count in cache
        $pendingWithContinueSending    = $emailModel->getPendingLeads($emailWithContinueSending, null, true);
        $pendingWithoutContinueSending = $emailModel->getPendingLeads($emailWithoutContinueSending, null, true);

        $crawler = $this->client->request(Request::METHOD_GET, '/s/emails');
        $this->assertEquals(2, $crawler->filter('.fa-pause-circle-o')->count());
        $this->assertEquals(0, $crawler->filter('.fa-check-circle')->count());

        $crawler = $this->client->request(Request::METHOD_GET, "/s/emails/view/{$emailWithContinueSending->getId()}");
        // check If  element with class label-success has value Running
        $this->assertStringContainsString('Running', $crawler->filter('.label-success')->text());

        $crawler = $this->client->request(Request::METHOD_GET, "/s/emails/view/{$emailWithoutContinueSending->getId()}");
        // check If  element with class label-success has value Running
        $this->assertStringContainsString('Running', $crawler->filter('.label-success')->text());

        [$sentCount] = $emailModel->sendEmailToLists($emailWithContinueSending, [$segment]);
        $this->assertEquals($sentCount, 20);

        [$sentCount] = $emailModel->sendEmailToLists($emailWithoutContinueSending, [$segment]);
        $this->assertEquals($sentCount, 20);

        // refresh pending count in cache
        $pendingWithContinueSending    = $emailModel->getPendingLeads($emailWithContinueSending, null, true);
        $pendingWithoutContinueSending = $emailModel->getPendingLeads($emailWithoutContinueSending, null, true);

        $crawler = $this->client->request(Request::METHOD_GET, '/s/emails');
        $this->assertEquals(1, $crawler->filter('.fa-pause-circle-o')->count());
        $this->assertEquals(1, $crawler->filter('.fa-check-circle')->count());

        $crawler = $this->client->request(Request::METHOD_GET, "/s/emails/view/{$emailWithContinueSending->getId()}");
        // check If  element with class label-success has value Running
        $this->assertStringContainsString('Running', $crawler->filter('.label-success')->text());

        $crawler = $this->client->request(Request::METHOD_GET, "/s/emails/view/{$emailWithoutContinueSending->getId()}");
        // check If  element with class label-success has value Running
        $this->assertStringContainsString('Sent', $crawler->filter('.label-success')->text());

        $this->addContactsToSegment($contactsAfter, $segment);

        [$sentCount] = $emailModel->sendEmailToLists($emailWithContinueSending, [$segment]);
        $this->assertEquals($sentCount, 3);

        [$sentCount] = $emailModel->sendEmailToLists($emailWithoutContinueSending, [$segment]);
        $this->assertEquals($sentCount, 0);
    }
}
