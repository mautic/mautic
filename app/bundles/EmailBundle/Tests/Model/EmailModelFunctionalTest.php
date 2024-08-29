<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Model;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Mautic\ChannelBundle\Entity\MessageQueue;
use Mautic\ChannelBundle\Entity\MessageQueueRepository;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\LeadBundle\Entity\FrequencyRule;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListLead;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Entity\Redirect;
use Mautic\PageBundle\Entity\Trackable;
use PHPUnit\Framework\Assert;

class EmailModelFunctionalTest extends MauticMysqlTestCase
{
    private const EMAILS_A_MONTH = 2;
    private bool $useDefaultFrequencyRules;
    private EmailModel $emailModel;

    protected function setUp(): void
    {
        $this->useDefaultFrequencyRules = ' with data set "Default Frequency Rules"' === $this->getDataSetAsString(false);

        $this->configParams['email_frequency_number'] = $this->useDefaultFrequencyRules ? self::EMAILS_A_MONTH : 0;
        $this->configParams['email_frequency_time']   = 'MONTH';
        parent::setUp();

        $emailModel = static::getContainer()->get('mautic.email.model.email');
        \assert($emailModel instanceof EmailModel);
        $this->emailModel = $emailModel;
    }

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

        [$sentCount] = $this->emailModel->sendEmailToLists($email, [$segment], null, null, null, null, null, 3, 1);
        $this->assertEquals($sentCount, 7);
        [$sentCount] = $this->emailModel->sendEmailToLists($email, [$segment], null, null, null, null, null, 3, 2);
        $this->assertEquals($sentCount, 8);
        [$sentCount] = $this->emailModel->sendEmailToLists($email, [$segment], null, null, null, null, null, 3, 3);
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

        $contactModel = static::getContainer()->get('mautic.lead.model.lead');
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

        [$sentCount, $failedCount, $failedRecipientsByList] = $this->emailModel->sendEmailToLists($email, [$segment], 4, 2);
        $this->assertEquals($sentCount, 4);
        [$sentCount, $failedCount, $failedRecipientsByList] = $this->emailModel->sendEmailToLists($email, [$segment], 3, 2);
        $this->assertEquals($sentCount, 3);
        [$sentCount, $failedCount, $failedRecipientsByList] = $this->emailModel->sendEmailToLists($email, [$segment], 2);
        $this->assertEquals($sentCount, 2);
        [$sentCount, $failedCount, $failedRecipientsByList] = $this->emailModel->sendEmailToLists($email, [$segment], 4);
        $this->assertEquals($sentCount, 1);

        $email                                              = $this->createEmail($segment);
        [$sentCount, $failedCount, $failedRecipientsByList] = $this->emailModel->sendEmailToLists($email, [$segment]);
        $this->assertEquals($sentCount, 10);

        $email                                              = $this->createEmail($segment);
        [$sentCount, $failedCount, $failedRecipientsByList] = $this->emailModel->sendEmailToLists($email, [$segment], null, 2);
        $this->assertEquals($sentCount, 10);
    }

    public function testNotOverwriteChildrenTranslationEmailAfterSaveParent(): void
    {
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

        $this->em->clear();

        $parentEmail->setName('Test change');
        $this->emailModel->saveEntity($parentEmail);

        self::assertSame($customHtmlParent, $parentEmail->getCustomHtml());
        self::assertSame($customHtmlChildren, $childrenEmail->getCustomHtml());
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    private function emulateEmailStat(Lead $lead, Email $email, bool $isRead): void
    {
        $stat = new Stat();
        $stat->setEmailAddress('test@test.com');
        $stat->setLead($lead);
        $stat->setDateSent(new \DateTime('2023-07-22'));
        $stat->setEmail($email);
        $stat->setIsRead($isRead);
        $this->em->persist($stat);
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    private function emulateClick(Lead $lead, Email $email, int $hits, int $uniqueHits): void
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
        $pageHit->setDateHit(new \DateTime());
        $pageHit->setCode(200);
        $pageHit->setUrl($redirect->getUrl());
        $pageHit->setTrackingId($redirect->getRedirectId());
        $pageHit->setSource('email');
        $pageHit->setSourceId($email->getId());
        $this->em->persist($pageHit);
    }

    /**
     * @throws ORMException
     * @throws Exception
     */
    public function testGetEmailCountryStatsSingleEmail(): void
    {
        $dateFrom     = new \DateTimeImmutable('2023-07-21');
        $dateTo       = new \DateTimeImmutable('2023-07-24');
        $leadsPayload = [
            [
                'email'   => 'example1@test.com',
                'country' => 'Italy',
                'read'    => true,
                'click'   => true,
            ],
            [
                'email'   => 'example2@test.com',
                'country' => 'Italy',
                'read'    => true,
                'click'   => false,
            ],
            [
                'email'   => 'example3@test.com',
                'country' => 'Italy',
                'read'    => false,
                'click'   => false,
            ],
            [
                'email'   => 'example4@test.com',
                'country' => '',
                'read'    => true,
                'click'   => true,
            ],
            [
                'email'   => 'example5@test.com',
                'country' => 'Poland',
                'read'    => true,
                'click'   => false,
            ],
            [
                'email'   => 'example6@test.com',
                'country' => 'Poland',
                'read'    => true,
                'click'   => true,
            ],
        ];

        $email = new Email();
        $email->setName('Test email');
        $this->em->persist($email);
        $this->em->flush();

        foreach ($leadsPayload as $l) {
            $lead = new Lead();
            $lead->setEmail($l['email']);
            $lead->setCountry($l['country']);
            $this->em->persist($lead);

            $this->emulateEmailStat($lead, $email, $l['read']);

            if ($l['read'] && $l['click']) {
                $hits       = rand(1, 5);
                $uniqueHits = rand(1, $hits);
                $this->emulateClick($lead, $email, $hits, $uniqueHits);
            }
        }
        $this->em->flush();
        $results = $this->emailModel->getCountryStats($email, $dateFrom, $dateTo);

        $this->assertCount(2, $results);
        $this->assertSame([
            'clicked_through_count' => [
                [
                    'clicked_through_count' => '1',
                    'country'               => '',
                ],
                [
                    'clicked_through_count' => '1',
                    'country'               => 'Italy',
                ],
                [
                    'clicked_through_count' => '1',
                    'country'               => 'Poland',
                ],
            ],
            'read_count' => [
                [
                    'read_count'            => '1',
                    'country'               => '',
                ],
                [
                    'read_count'            => '2',
                    'country'               => 'Italy',
                ],
                [
                    'read_count'            => '2',
                    'country'               => 'Poland',
                ],
            ],
        ], $results);
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function testGetContextEntity(): void
    {
        $email = new Email();
        $email->setName('Test email');
        $this->em->persist($email);
        $this->em->flush();

        $id     = $email->getId();
        $result = $this->emailModel->getEntity($id);

        $this->assertSame($email, $result);
    }

    /**
     * @return iterable<string, null[]>
     */
    public function dataFrequencyRules(): iterable
    {
        yield 'Custom Frequency Rules' => [null];
        yield 'Default Frequency Rules' => [null];
    }

    /**
     * @dataProvider dataFrequencyRules
     */
    public function testFrequencyRulesAreAppliedWhenSendToDncIsNo(): void
    {
        $contact = $this->createContact();
        $email   = $this->createTemplateEmail();
        $this->createFrequencyRule($contact);
        $this->createEmailStats($email, $contact);
        $this->em->flush();

        $this->sendEmail($email, $contact);
        $this->assertEmailIsPostponed($email, $contact);
    }

    /**
     * @dataProvider dataFrequencyRules
     */
    public function testFrequencyRulesAreNotAppliedWhenSendToDncIsTrue(): void
    {
        $contact = $this->createContact();
        $email   = $this->createTemplateEmail();
        $email->setSendToDnc(true);
        $this->em->persist($email);
        $this->createFrequencyRule($contact);
        $this->createEmailStats($email, $contact);
        $this->em->flush();

        $this->sendEmail($email, $contact);
        $this->assertEmailIsNotPostponed();
    }

    /**
     * @dataProvider dataFrequencyRules
     */
    public function testEmailsWithSendToDncSetToYesAreNotCountedTowardsFrequencyRules(): void
    {
        $contact     = $this->createContact();
        $emailToSend = $this->createTemplateEmail();
        $emailDncYes = $this->createTemplateEmail();
        $emailDncYes->setSendToDnc(true);
        $this->em->persist($emailToSend);
        $this->createFrequencyRule($contact);
        $this->createEmailStats($emailDncYes, $contact);
        $this->em->flush();

        $this->sendEmail($emailToSend, $contact);
        $this->assertEmailIsNotPostponed();
    }

    private function createContact(): Lead
    {
        $contact = new Lead();
        $contact->setEmail('john@doe.com');
        $contact->setFirstname('John');
        $contact->setLastname('Doe');
        $this->em->persist($contact);

        return $contact;
    }

    private function createTemplateEmail(): Email
    {
        $email = new Email();
        $email->setName('Test');
        $email->setSubject('Test');
        $email->setCustomHTML('test EN');
        $email->setEmailType('template');
        $email->setLanguage('en');
        $this->em->persist($email);

        return $email;
    }

    private function createFrequencyRule(Lead $contact): void
    {
        if ($this->useDefaultFrequencyRules) {
            return;
        }

        $frequencyRule = new FrequencyRule();
        $frequencyRule->setLead($contact);
        $frequencyRule->setDateAdded(new \DateTime());
        $frequencyRule->setChannel('email');
        $frequencyRule->setFrequencyNumber(self::EMAILS_A_MONTH);
        $frequencyRule->setFrequencyTime('MONTH');
        $this->em->persist($frequencyRule);
    }

    private function createEmailStats(Email $email, Lead $contact): void
    {
        $exceedFrequencyRule = self::EMAILS_A_MONTH + 1;

        for ($i = 0; $i < $exceedFrequencyRule; ++$i) {
            $stat = new Stat();
            $stat->setEmail($email);
            $stat->setLead($contact);
            $stat->setEmailAddress($contact->getEmail());
            $stat->setDateSent(new \DateTime('-1 day'));
            $this->em->persist($stat);
        }
    }

    private function sendEmail(Email $email, Lead $contact): void
    {
        $this->emailModel->sendEmail(
            $email,
            [
                [
                    'id'        => $contact->getId(),
                    'email'     => $contact->getEmail(),
                    'firstname' => $contact->getFirstname(),
                    'lastname'  => $contact->getLastname(),
                ],
            ]
        );
    }

    private function assertEmailIsNotPostponed(): void
    {
        $messageQueueRepository = $this->em->getRepository(MessageQueue::class);
        \assert($messageQueueRepository instanceof MessageQueueRepository);

        Assert::assertSame(0, $messageQueueRepository->count([]), 'Email should not be postponed.');
    }

    private function assertEmailIsPostponed(Email $email, Lead $contact): void
    {
        $messageQueueRepository = $this->em->getRepository(MessageQueue::class);
        \assert($messageQueueRepository instanceof MessageQueueRepository);

        $queuedMessages = $messageQueueRepository->findBy([]);
        Assert::assertCount(1, $queuedMessages, 'Email should be postponed.');

        $queuedMessage = reset($queuedMessages);
        Assert::assertInstanceOf(MessageQueue::class, $queuedMessage);
        Assert::assertSame('email', $queuedMessage->getChannel());
        Assert::assertSame($email->getId(), $queuedMessage->getChannelId());
        Assert::assertSame($contact, $queuedMessage->getLead());
        Assert::assertSame($queuedMessage::STATUS_PENDING, $queuedMessage->getStatus());
    }
}
