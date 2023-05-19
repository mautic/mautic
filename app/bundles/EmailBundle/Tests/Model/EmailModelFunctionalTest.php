<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Model;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListLead;
use Mautic\LeadBundle\Model\LeadModel;

class EmailModelFunctionalTest extends MauticMysqlTestCase
{
    public function testSendEmailToListsInThreads(): void
    {
        $contacts = $this->generateContacts(23);
        $segment  = $this->createSegment();
        $this->addContactsToSegment($contacts, $segment);
        $email = $this->createEmail($segment);

        $emailModel                                             =  self::$container->get('mautic.email.model.email');
        \assert($emailModel instanceof EmailModel);
        [$sentCount, ,] = $emailModel->sendEmailToLists($email, [$segment], null, false, null, null, null, 3, 1);
        $this->assertEquals($sentCount, 8);
        [$sentCount, ,] = $emailModel->sendEmailToLists($email, [$segment], null, false, null, null, null, 3, 2);
        $this->assertEquals($sentCount, 7);
        [$sentCount, ,] = $emailModel->sendEmailToLists($email, [$segment], null, false, null, null, null, 3, 3);
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
        $email->setEmailType('list');
        $email->setPublishUp(new \DateTime('-1 day'));
        $email->setIsPublished(true);
        $email->addList($segment);
        $this->em->persist($email);
        $this->em->flush();

        return $email;
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

    public function testGetDeliveredCount(): void
    {
        $contact1 = $this->createContact('john@example.com');
        $contact2 = $this->createContact('paul@example.com');

        $this->em->flush();
        $email   = $this->createEmail(
            'Email A',
            'Email A Subject',
            'list',
            'beefree-empty',
            'Test html'
        );
        $this->em->flush();

        $this->createEmailStat($contact1, $email);
        $this->createEmailStat($contact2, $email);
        $email->setSentCount(2);
        $this->em->persist($email);
        $this->em->flush();

        $this->createDoNotContact($contact2, $email, DoNotContact::BOUNCED);
        $this->em->flush();

        /** @var EmailModel $emailModel */
        $emailModel = self::$container->get('mautic.email.model.email');

        $this->assertEquals(1, $emailModel->getDeliveredCount($email));
    }

    public function testGetDeliveredCountWithTranslations(): void
    {
        $contactEn1 = $this->createContact('john@example.com');
        $contactEn2 = $this->createContact('paul@example.com');
        $contactPl1 = $this->createContact('szczepan@example.com');
        $contactPl2 = $this->createContact('jadwiga@example.com');
        $this->em->flush();

        $emailEn   = $this->createEmail(
            'Email EN',
            'Email EN Subject',
            'list',
            'beefree-empty',
            'Test html EN'
        );
        $emailEn->setLanguage('en');
        $this->em->flush();

        $emailPl   = $this->createEmail(
            'Email PL',
            'Email PL Subject',
            'list',
            'beefree-empty',
            'Test html PL'
        );
        $emailEn->setLanguage('pl_PL');
        $this->em->persist($emailPl);
        $this->em->flush();

        $emailPl->setTranslationParent($emailEn);
        $emailEn->addTranslationChild($emailPl);
        $this->createEmailStat($contactEn1, $emailEn);
        $this->createEmailStat($contactEn2, $emailEn);
        $this->createEmailStat($contactPl1, $emailPl);
        $this->createEmailStat($contactPl2, $emailPl);
        $emailEn->setSentCount(2);
        $emailPl->setSentCount(2);
        $this->em->persist($emailEn);
        $this->em->persist($emailPl);
        $this->em->flush();

        $this->createDoNotContact($contactEn1, $emailEn, DoNotContact::BOUNCED);
        $this->createDoNotContact($contactPl1, $emailPl, DoNotContact::BOUNCED);
        $this->em->flush();
        $this->em->clear();

        /** @var EmailModel $emailModel */
        $emailModel = self::$container->get('mautic.email.model.email');
        $emailEn    = $emailModel->getEntity($emailEn->getId());

        $this->assertEquals(2, $emailModel->getDeliveredCount($emailEn, true));
    }

    /**
     * @param array<integer, mixed> $segments
     *
     * @throws \Doctrine\ORM\ORMException
     */
    private function createEmail(string $name, string $subject, string $emailType, string $template, string $customHtml, array $segments = []): Email
    {
        $email = new Email();
        $email->setName($name);
        $email->setSubject($subject);
        $email->setEmailType($emailType);
        $email->setTemplate($template);
        $email->setCustomHtml($customHtml);
        $email->setLists($segments);
        $this->em->persist($email);

        return $email;
    }

    private function createContact(string $email): Lead
    {
        $lead = new Lead();
        $lead->setEmail($email);
        $this->em->persist($lead);

        return $lead;
    }

    private function createEmailStat(Lead $contact, Email $email): Stat
    {
        $emailStat = new Stat();
        $emailStat->setEmail($email);
        $emailStat->setLead($contact);
        $emailStat->setEmailAddress($contact->getEmail());
        $emailStat->setDateSent(new \DateTime());
        $this->em->persist($emailStat);

        return $emailStat;
    }

    private function createDoNotContact(Lead $contact, Email $email, int $reason): DoNotContact
    {
        $dnc = new DoNotContact();
        $dnc->setLead($contact);
        $dnc->setChannel('email');
        $dnc->setChannelId($email->getId());
        $dnc->setDateAdded(new \DateTime());
        $dnc->setReason($reason);
        $dnc->setComments('Test DNC');
        $this->em->persist($dnc);

        return $dnc;
    }
}
