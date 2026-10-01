<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Functional;

use Mautic\CategoryBundle\Entity\Category;
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
        $segment  = $this->createSegment();
        $category = $this->createCategory('Newsletter');
        $parent   = $this->createEmail('Email EN', 'en', $segment, null, $category);
        $this->createEmail('Email PT', 'pt_PT', $segment, $parent, $category);
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

        $category = $this->createCategory('Newsletter');
        $parent   = $this->createEmail('Email EN', 'en', $segment, null, $category);
        $child    = $this->createEmail('Email PT', 'pt_PT', $segment, $parent, $category);
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

    /**
     * A translated broadcast leaves its translation children in the entity manager
     * (they are hydrated by Email::getRelatedEntityIds()) while the parent itself is
     * detached at the end of its iteration in BroadcastSubscriber::onBroadcast().
     *
     * Any later flush in the same run - such as the auto-unpublish of the next
     * broadcast - then walks those still-managed children and finds a detached
     * translation parent behind an association that does not cascade persist,
     * which aborts the whole cron run with an ORMInvalidArgumentException.
     */
    public function testAutoUnpublishOfALaterBroadcastDoesNotBreakOnADetachedTranslationParent(): void
    {
        $contacts = [
            $this->createContact('en-locale@example.com', 'en'),
            $this->createContact('pt-locale@example.com', 'pt_PT'),
        ];
        $segment = $this->createSegment();
        $this->addContactsToSegment($contacts, $segment);

        $category = $this->createCategory('Newsletter');
        // Processed first: a translated email whose children stay managed after it is detached.
        $translatedParent = $this->createEmail('Email EN', 'en', $segment, null, $category);
        $this->createEmail('Email PT', 'pt_PT', $segment, $translatedParent, $category);
        // Processed afterwards: its auto-unpublish triggers the flush that walks the leftovers.
        $secondEmail = $this->createEmail('Email Second', 'en', $segment, null, $category);
        $this->em->clear();

        // First run drains both broadcasts, so neither is unpublished yet.
        $commandTester = $this->testSymfonyCommand('mautic:broadcasts:send');
        Assert::assertSame(0, $commandTester->getStatusCode());

        $this->setUpSymfony($this->configParams);

        // Second run has nothing pending for either broadcast, so both auto-unpublish.
        $commandTester = $this->testSymfonyCommand('mautic:broadcasts:send');
        Assert::assertSame(
            0,
            $commandTester->getStatusCode(),
            'The broadcast run must not abort: '.$commandTester->getDisplay()
        );

        $this->em->clear();
        $repository = $this->em->getRepository(Email::class);
        \assert($repository instanceof EmailRepository);

        foreach ([$translatedParent->getId(), $secondEmail->getId()] as $emailId) {
            $email = $repository->find($emailId);
            \assert($email instanceof Email);
            Assert::assertFalse(
                $email->isPublished(),
                sprintf('Email "%s" must be unpublished once it has no pending contacts left.', $email->getName())
            );
        }
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

    private function createCategory(string $title): Category
    {
        $category = new Category();
        $category->setTitle($title);
        $category->setAlias(strtolower($title));
        $category->setBundle('email');
        $this->em->persist($category);
        $this->em->flush();

        return $category;
    }

    private function createEmail(string $name, string $language, LeadList $segment, ?Email $translationParent = null, ?Category $category = null): Email
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

        if (null !== $category) {
            $email->setCategory($category);
        }

        if (null !== $translationParent) {
            $email->setTranslationParent($translationParent);
            $translationParent->addTranslationChild($email);
        }

        $this->em->persist($email);
        $this->em->flush();

        return $email;
    }
}
