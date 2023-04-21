<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Entity;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\EmailRepository;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListLead;
use Mautic\LeadBundle\Model\LeadModel;
use PHPUnit\Framework\Assert;

/**
 * This test ensures that the pending query will work even if a contact was deleted between batches.
 * After the refactoring from NOT EXISTS to NOT IN the single deleted contact could cause the
 * pending query to find no contacts due to null value in the lead_id column.
 */
class PendingQueryFunctionalTest extends MauticMysqlTestCase
{
    public function testDelayedSends(): void
    {
        $emailRepository = $this->em->getRepository(Email::class);
        \assert($emailRepository instanceof EmailRepository);

        $contactCount  = 4;
        $oneBatchCount = $contactCount / 2;
        $contacts      = $this->generateContacts($contactCount);
        $batch1        = array_slice($contacts, 0, $oneBatchCount);
        $segment       = $this->createSegment();
        $email         = $this->createEmail($segment);
        $this->addContactsToSegment($contacts, $segment);

        Assert::assertSame($contactCount, (int) $emailRepository->getEmailPendingLeads($email->getId(), null, null, true));

        $this->emulateEmailSend($email, $batch1);

        Assert::assertSame($oneBatchCount, (int) $emailRepository->getEmailPendingLeads($email->getId(), null, null, true));

        $this->em->remove($batch1[0]);
        $this->em->flush();

        // The pending count must be the same even if one of the email_stat records has lead_id = null.
        Assert::assertSame($oneBatchCount, (int) $emailRepository->getEmailPendingLeads($email->getId(), null, null, true));
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
        $email->setName('Email A');
        $email->setSubject('Email A Subject');
        $email->setEmailType('list');
        $email->addList($segment);
        $this->em->persist($email);
        $this->em->flush();

        return $email;
    }

    /**
     * @param Lead[] $contacts
     */
    private function emulateEmailSend(Email $email, array $contacts): void
    {
        foreach ($contacts as $contact) {
            $emailStat = new Stat();
            $emailStat->setEmail($email);
            $emailStat->setEmailAddress($contact->getEmail());
            $emailStat->setLead($contact);
            $emailStat->setDateSent(new \DateTime());
            $this->em->persist($emailStat);
        }

        $this->em->flush();
    }
}
