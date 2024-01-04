<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Model;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListLead;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Entity\Redirect;
use Mautic\PageBundle\Entity\Trackable;

class EmailModelFunctionalTest extends MauticMysqlTestCase
{
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
        \assert($emailModel instanceof EmailModel);
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
    private function addContactsToSegment(array $contacts, LeadList $segment): void
    {
        foreach ($contacts as $contact) {
            $reference = new ListLead();
            $reference->setLead($contact);
            $reference->setList($segment);
            $reference->setDateAdded(new \DateTime());
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

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    private function emulateEmailStat(Lead $lead, Email $email, bool $isRead, \DateTime $dateSent = null, \DateTime $dateRead = null): void
    {
        $stat = new Stat();
        $stat->setEmailAddress('test@test.com');
        $stat->setLead($lead);
        $stat->setDateSent($dateSent ?? new \DateTime('2023-07-22'));
        $stat->setDateRead($dateRead ?? new \DateTime('2023-07-22'));
        $stat->setEmail($email);
        $stat->setIsRead($isRead);
        $this->em->persist($stat);
        $this->em->flush();
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    private function emulateClick(Lead $lead, Email $email, int $hits, int $uniqueHits, \DateTime $dateHit = null): void
    {
        $ipAddress = new IpAddress();
        $ipAddress->setIpAddress('127.0.0.1');
        $this->em->persist($ipAddress);
        $this->em->flush();

        $redirect = new Redirect();
        $redirect->setRedirectId(uniqid());
        $redirect->setUrl('https://example.com');
        $redirect->setHits($hits);
        $redirect->setUniqueHits($uniqueHits);
        $this->em->persist($redirect);

        $trackable = new Trackable();
        $trackable->setChannelId($email->getId());
        $trackable->setChannel('email');
        $trackable->setHits($hits);
        $trackable->setUniqueHits($uniqueHits);
        $trackable->setRedirect($redirect);
        $this->em->persist($trackable);

        $pageHit = new Hit();
        $pageHit->setRedirect($redirect);
        $pageHit->setIpAddress($ipAddress);
        $pageHit->setEmail($email);
        $pageHit->setLead($lead);
        $pageHit->setDateHit($dateHit ?? new \DateTime());
        $pageHit->setCode(200);
        $pageHit->setUrl($redirect->getUrl());
        $pageHit->setTrackingId($redirect->getRedirectId());
        $pageHit->setSource('email');
        $pageHit->setSourceId($email->getId());
        $this->em->persist($pageHit);
        $this->em->flush();
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws Exception
     */
    public function testGetEmailDayStats(): void
    {
        /** @var EmailModel $emailModel */
        $emailModel     = $this->getContainer()->get('mautic.email.model.email');
        $statRepository = $emailModel->getStatRepository();

        $hits         = rand(1, 5);
        $uniqueHits   = rand(1, $hits);

        $email = new Email();
        $email->setName('Test email');
        $this->em->persist($email);
        $this->em->flush();

        $lead = new Lead();
        $lead->setEmail('example@example.com');
        $this->em->persist($lead);

        $this->emulateEmailStat($lead, $email, true, new \DateTime('2023-07-31 12:51'), new \DateTime('2023-08-01 14:20'));
        $this->emulateClick($lead, $email, $hits, $uniqueHits, new \DateTime('2023-08-03 17:05'));

        $results = $statRepository->getEmailDayStats($lead, '+00:00');

        $this->assertCount(7, $results);
        $this->assertSame(
            [0, 1, 2, 3, 4, 5, 6],
            array_keys($results)
        );
        $this->assertSame(
            ['day', 'sent_count', 'read_count', 'hit_count'],
            array_keys($results[0])
        );
        $this->assertSame('1', $results[0]['sent_count']);
        $this->assertSame('0', $results[0]['read_count']);
        $this->assertSame('0', $results[0]['hit_count']);
        $this->assertSame('0', $results[1]['sent_count']);
        $this->assertSame('1', $results[1]['read_count']);
        $this->assertSame('1', $results[3]['hit_count']);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     */
    public function testGetEmailTimeStats(): void
    {
        /** @var EmailModel $emailModel */
        $emailModel     = $this->getContainer()->get('mautic.email.model.email');
        $statRepository = $emailModel->getStatRepository();

        $hits         = rand(1, 5);
        $uniqueHits   = rand(1, $hits);

        $email = new Email();
        $email->setName('Test email');
        $this->em->persist($email);
        $this->em->flush();

        $lead = new Lead();
        $lead->setEmail('example@example.com');
        $this->em->persist($lead);

        $this->emulateEmailStat($lead, $email, true, new \DateTime('2023-07-31 12:51'), new \DateTime('2023-08-01 14:20'));
        $this->emulateClick($lead, $email, $hits, $uniqueHits, new \DateTime('2023-08-03 17:05'));

        $results = $statRepository->getEmailTimeStats($lead, '+00:00');

        $this->assertCount(24, $results);
        $this->assertSame(
            [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23],
            array_keys($results)
        );
        $this->assertSame(
            ['hour', 'sent_count', 'read_count', 'hit_count'],
            array_keys($results[0])
        );
        $this->assertSame('12', $results[12]['hour']);
        $this->assertSame('1', $results[12]['sent_count']);
        $this->assertSame('0', $results[12]['read_count']);
        $this->assertSame('0', $results[14]['hit_count']);
        $this->assertSame('0', $results[14]['sent_count']);
        $this->assertSame('1', $results[14]['read_count']);
        $this->assertSame('1', $results[17]['hit_count']);
    }
}
