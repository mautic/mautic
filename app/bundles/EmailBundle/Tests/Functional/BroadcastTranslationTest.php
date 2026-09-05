<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\EmailRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListLead;
use Mautic\LeadBundle\Model\LeadModel;
use PHPUnit\Framework\Assert;

final class BroadcastTranslationTest extends MauticMysqlTestCase
{
    /**
     * Disabled so the kernel can be rebooted mid-test to simulate consecutive cron runs.
     */
    protected $useCleanupRollback = false;

    public function testTranslationChildrenAreNotBroadcastIndependently(): void
    {
        $segment = $this->createSegment();
        $parent  = $this->createEmail('Email EN', 'en', $segment);
        $this->createEmail('Email PT', 'pt_PT', $segment, $parent);
        $this->em->clear();

        $repository = $this->em->getRepository(Email::class);
        \assert($repository instanceof EmailRepository);

        $broadcastIds = [];
        foreach ($repository->getPublishedBroadcastsIterable() as $email) {
            $broadcastIds[] = $email->getId();
        }

        Assert::assertSame([$parent->getId()], $broadcastIds);
    }

    public function testBroadcastSendsTranslationByPreferredLocale(): void
    {
        // Contacts are created before the segment because LeadModel::saveEntity()
        // clears the entity manager; a detached segment would get cascade-persisted
        // as a duplicate when the emails are flushed.
        $contacts = [
            $this->createContact('en-locale@example.com', 'en'),
            $this->createContact('no-locale-1@example.com', null),
            $this->createContact('pt-locale@example.com', 'pt_PT'),
            $this->createContact('no-locale-2@example.com', null),
        ];
        $segment = $this->createSegment();
        $this->addContactsToSegment($contacts, $segment);

        $parent = $this->createEmail('Email EN', 'en', $segment);
        $child  = $this->createEmail('Email PT', 'pt_PT', $segment, $parent);
        $this->em->clear();

        // A limit lower than the segment size ensures a single broadcast cannot
        // drain the whole segment in one run, which is the scenario where
        // translation children used to be sent as independent broadcasts.
        $commandTester = $this->testSymfonyCommand('mautic:broadcasts:send', ['--limit' => 2]);
        Assert::assertSame(0, $commandTester->getStatusCode());

        // Reboot the kernel to simulate the next cron run happening in a fresh process.
        $this->setUpSymfony($this->configParams);

        $commandTester = $this->testSymfonyCommand('mautic:broadcasts:send', ['--limit' => 2]);
        Assert::assertSame(0, $commandTester->getStatusCode());

        $stats = $this->em->getConnection()->fetchAllAssociative(
            'SELECT email_address, email_id FROM '.MAUTIC_TABLE_PREFIX.'email_stats'
        );

        Assert::assertCount(4, $stats, 'Every contact must receive exactly one email.');

        $sentEmailIdByAddress = [];
        foreach ($stats as $stat) {
            $sentEmailIdByAddress[$stat['email_address']] = (int) $stat['email_id'];
        }
        ksort($sentEmailIdByAddress);

        Assert::assertSame(
            [
                'en-locale@example.com'   => $parent->getId(),
                'no-locale-1@example.com' => $parent->getId(),
                'no-locale-2@example.com' => $parent->getId(),
                'pt-locale@example.com'   => $child->getId(),
            ],
            $sentEmailIdByAddress,
            'Contacts must receive the translation matching their preferred locale and the parent otherwise.'
        );
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

    private function createContact(string $emailAddress, ?string $preferredLocale): Lead
    {
        $contact = new Lead();
        $contact->setEmail($emailAddress);

        if (null !== $preferredLocale) {
            $contact->addUpdatedField('preferred_locale', $preferredLocale);
        }

        $contactModel = static::getContainer()->get('mautic.lead.model.lead');
        \assert($contactModel instanceof LeadModel);
        $contactModel->saveEntity($contact);

        return $contact;
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
            // Segment membership must predate the emails' publish up date, otherwise
            // the contacts are not considered pending (see EmailModel::getPendingLeads()
            // passing the publish up date as the send stop date).
            $reference->setDateAdded(new \DateTime('-1 week'));
            $this->em->persist($reference);
        }

        $this->em->flush();
    }

    private function createEmail(string $name, string $language, LeadList $segment, ?Email $translationParent = null): Email
    {
        $email = new Email();
        $email->setName($name);
        $email->setSubject($name.' Subject');
        $email->setCustomHtml($name.' content');
        $email->setEmailType('list');
        $email->setLanguage($language);
        $email->setPublishUp(new \DateTime('-1 day'));
        $email->setIsPublished(true);
        $email->addList($segment);

        if (null !== $translationParent) {
            $email->setTranslationParent($translationParent);
            $translationParent->addTranslationChild($email);
        }

        $this->em->persist($email);
        $this->em->flush();

        return $email;
    }
}
