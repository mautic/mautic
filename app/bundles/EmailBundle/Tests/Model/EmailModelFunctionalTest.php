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
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\FrequencyRule;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListLead;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Entity\Redirect;
use Mautic\PageBundle\Entity\Trackable;
use PHPUnit\Framework\Assert;

final class EmailModelFunctionalTest extends MauticMysqlTestCase
{
    use CreateTestEntitiesTrait;

    private const EMAILS_A_MONTH = 2;

    private bool $useDefaultFrequencyRules;

    private EmailModel $emailModel;

    protected function setUp(): void
    {
        $this->useDefaultFrequencyRules = ' with data set "Default Frequency Rules"' === $this->dataSetAsString();

        $this->configParams['email_frequency_number'] = $this->useDefaultFrequencyRules ? self::EMAILS_A_MONTH : 0;
        $this->configParams['email_frequency_time']   = 'MONTH';
        parent::setUp();

        $emailModel = static::getContainer()->get('mautic.email.model.email');
        $this->assertInstanceOf(EmailModel::class, $emailModel);
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
        $this->assertEquals(7, $sentCount);
        [$sentCount] = $this->emailModel->sendEmailToLists($email, [$segment], null, null, null, null, null, 3, 2);
        $this->assertEquals(8, $sentCount);
        [$sentCount] = $this->emailModel->sendEmailToLists($email, [$segment], null, null, null, null, null, 3, 3);
        $this->assertEquals(8, $sentCount);
    }

    public function testGetEmailGeneralStats(): void
    {
        $contacts = $this->generateContacts(12);
        $segment  = $this->createSegment();
        $this->addContactsToSegment($contacts, $segment);
        $email = $this->createEmail($segment);

        // Send email to segment
        [$sentCount] = $this->emailModel->sendEmailToLists($email, [$segment]);

        // Emulate email reads
        $statRepository = $this->em->getRepository(Stat::class);
        $stats          = $statRepository->findBy([
            'email' => $email,
            'lead'  => $contacts,
        ]);
        for ($index = 0; $index < $readCount = 4; ++$index) {
            $this->emulateEmailRead($stats[$index]);
        }

        // Emulate clicks
        $this->emulateClick($contacts[0], $email, 1, 1);
        $this->emulateClick($contacts[1], $email, 1, 1);

        // Emulate unsubscribing and bounces
        $this->createDnc('email', $contacts[3], DoNotContact::UNSUBSCRIBED, $email->getId());
        $this->createDnc('email', $contacts[4], DoNotContact::BOUNCED, $email->getId());

        // Emulate failed email
        $this->emulateEmailFailed($stats[5]);

        $this->em->flush();

        $dateFrom        = new \DateTime('-7 days');
        $dateTo          = new \DateTime();
        $unit            = 'D';
        $includeVariants = false;

        $result = $this->emailModel->getEmailGeneralStats($email, $includeVariants, $unit, $dateFrom, $dateTo);

        $this->assertIsArray($result);
        $this->assertCount(6, $result['datasets']);
        $this->assertEquals('Sent emails', $result['datasets'][0]['label']);
        $this->assertEquals([0, 0, 0, 0, 0, 0, 0, $sentCount], $result['datasets'][0]['data']);
        $this->assertEquals('Read emails', $result['datasets'][1]['label']);
        $this->assertEquals([0, 0, 0, 0, 0, 0, 0, $readCount], $result['datasets'][1]['data']);
        $this->assertEquals('Failed emails', $result['datasets'][2]['label']);
        $this->assertEquals([0, 0, 0, 0, 0, 0, 0, 1], $result['datasets'][2]['data']);
        $this->assertEquals('Unique Clicked', $result['datasets'][3]['label']);
        $this->assertEquals([0, 0, 0, 0, 0, 0, 0, 2], $result['datasets'][3]['data']);
        $this->assertEquals('Unsubscribed', $result['datasets'][4]['label']);
        $this->assertEquals([0, 0, 0, 0, 0, 0, 0, 1], $result['datasets'][4]['data']);
        $this->assertEquals('Bounced', $result['datasets'][5]['label']);
        $this->assertEquals([0, 0, 0, 0, 0, 0, 0, 1], $result['datasets'][5]['data']);
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
        $this->assertInstanceOf(LeadModel::class, $contactModel);
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

        [$sentCount, $failedCount, $failedRecipientsByList] = $this->emailModel->sendEmailToLists($email, [$segment], 4, 2);
        $this->assertEquals(4, $sentCount);
        [$sentCount, $failedCount, $failedRecipientsByList] = $this->emailModel->sendEmailToLists($email, [$segment], 3, 2);
        $this->assertEquals(3, $sentCount);
        [$sentCount, $failedCount, $failedRecipientsByList] = $this->emailModel->sendEmailToLists($email, [$segment], 2);
        $this->assertEquals(2, $sentCount);
        [$sentCount, $failedCount, $failedRecipientsByList] = $this->emailModel->sendEmailToLists($email, [$segment], 4);
        $this->assertEquals(1, $sentCount);

        $email                                              = $this->createEmail($segment);
        [$sentCount, $failedCount, $failedRecipientsByList] = $this->emailModel->sendEmailToLists($email, [$segment]);
        $this->assertEquals(10, $sentCount);

        $email                                              = $this->createEmail($segment);
        [$sentCount, $failedCount, $failedRecipientsByList] = $this->emailModel->sendEmailToLists($email, [$segment], null, 2);
        $this->assertEquals(10, $sentCount);
    }

    public function testSendEmailToListsWithContinueSendingFalse(): void
    {
        $contacts = $this->generateContacts(5);
        $segment  = $this->createSegment();
        $this->addContactsToSegment($contacts, $segment);

        // Create email with continueSending = false
        $email = new Email();
        $email->setName('Email with Continue Sending False');
        $email->setSubject('Email Subject');
        $email->setCustomHtml('Email content');
        $email->setEmailType('list');
        $email->setPublishUp(new \DateTime('-1 day'));
        $email->setContinueSending(false); // This should prevent sending
        $email->setIsPublished(true);
        $email->addList($segment);
        $this->em->persist($email);
        $this->em->flush();

        // Attempt to send emails - should send 0 because continueSending is false
        [$sentCount, $failedCount, $failedRecipientsByList] = $this->emailModel->sendEmailToLists($email, [$segment]);
        $this->assertEquals(0, $sentCount, 'No emails should be sent when continueSending is false');
        $this->assertEquals(0, $failedCount, 'No emails should fail when continueSending is false');
        $this->assertEmpty($failedRecipientsByList, 'No failed recipients when continueSending is false');
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
    private function emulateEmailStat(Lead $lead, Email $email, bool $isRead): Stat
    {
        $stat = new Stat();
        $stat->setEmailAddress('test@test.com');
        $stat->setLead($lead);
        $stat->setDateSent(new \DateTime('2023-07-22'));
        $stat->setEmail($email);
        $stat->setIsRead($isRead);
        $this->em->persist($stat);

        return $stat;
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

    private function emulateEmailRead(Stat $emailStat): void
    {
        $emailStat->setIsRead(true);
        $emailStat->setDateRead(new \DateTime());
        $emailStat->setOpenCount(1);
        $email = $emailStat->getEmail();
        $email->setReadCount($email->getReadCount() + 1);
        $this->em->persist($emailStat);
        $this->em->persist($email);
    }

    private function emulateEmailFailed(Stat $emailStat): void
    {
        $emailStat->setIsFailed(true);
        $this->em->persist($emailStat);
    }

    private function createDnc(string $channel, Lead $contact, int $reason, ?int $channelId = null): DoNotContact
    {
        $dnc = new DoNotContact();
        $dnc->setChannel($channel);
        $dnc->setLead($contact);
        $dnc->setReason($reason);
        $dnc->setDateAdded(new \DateTime());
        if ($channelId) {
            $dnc->setChannelId($channelId);
        }
        $this->em->persist($dnc);

        return $dnc;
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
                $hits       = random_int(1, 5);
                $uniqueHits = random_int(1, $hits);
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

    public function testReturnsContactAsIsIfNoId(): void
    {
        $contact = ['email' => 'test@example.com'];

        $result = $this->emailModel->enrichedContactWithCompanies($contact);

        $this->assertSame($contact, $result);
    }

    public function testReturnsContactAsIsIfCompaniesAlreadySet(): void
    {
        $contact = [
            'id'        => 1,
            'companies' => ['company1'],
        ];

        $result = $this->emailModel->enrichedContactWithCompanies($contact);

        $this->assertSame($contact, $result);
    }

    public function testEnrichesContactWithCompanies(): void
    {
        $company = $this->createCompany('Mautic', 'hello@mautic.org');
        $company->setCity('Pune');
        $company->setCountry('India');

        $this->em->persist($company);

        $contact = $this->createLead('John', 'Doe', 'test@domain.tld');
        $this->createPrimaryCompanyForLead($contact, $company);
        $this->em->flush();

        $contactArray = $contact->convertToArray();

        $result = $this->emailModel->enrichedContactWithCompanies($contactArray);

        $this->assertArrayHasKey('companies', $result);
        $this->assertSame($company->getName(), $result['companies'][0]['companyname']);
        $this->assertSame($company->getCity(), $result['companies'][0]['companycity']);
        $this->assertSame($company->getCountry(), $result['companies'][0]['companycountry']);
    }

    public function testEnrichesContactWithEmptyCompaniesIfNoneFound(): void
    {
        $contact = $this->createLead('John', 'Doe', 'test@domain.tld');
        $this->em->flush();

        $contactArray = $contact->convertToArray();

        $result = $this->emailModel->enrichedContactWithCompanies($contactArray);

        $this->assertArrayHasKey('companies', $result);
        $this->assertEmpty($result['companies']);
    }

    /**
     * @return iterable<string, null[]>
     */
    public static function dataFrequencyRules(): iterable
    {
        yield 'Custom Frequency Rules' => [null];
        yield 'Default Frequency Rules' => [null];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataFrequencyRules')]
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

    #[\PHPUnit\Framework\Attributes\DataProvider('dataFrequencyRules')]
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

    #[\PHPUnit\Framework\Attributes\DataProvider('dataFrequencyRules')]
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

    public function testGetEmailListStatsDateToIncludesTheWholeDay(): void
    {
        $contact  = $this->createContact();
        $segment  = $this->createSegment();
        $this->addContactsToSegment([$contact], $segment);
        $email   = $this->createEmail($segment);
        $stat    = $this->emulateEmailStat($contact, $email, false);
        $stat->setDateSent(new \DateTime('2026-05-02 06:31:32'));
        $this->em->flush();

        $stats = $this->emailModel->getEmailListStats($email, false, new \DateTime('2026-05-01'), new \DateTime('2026-05-02'));
        $data  = array_filter($stats['datasets'][0]['data'] ?? []);
        Assert::assertNotEmpty($data, 'The stats should not be empty');
    }

    public function testGetEmailGeneralStatsDateToIncludesTheWholeDay(): void
    {
        $contact = $this->createContact();
        $email   = $this->createTemplateEmail();
        $stat    = $this->emulateEmailStat($contact, $email, false);
        $stat->setDateSent(new \DateTime('2026-03-13 19:01:54'));
        $this->em->flush();

        $stats = $this->emailModel->getEmailGeneralStats($email, false, null, new \DateTime('2026-03-12'), new \DateTime('2026-03-13'));
        $data  = array_filter($stats['datasets'][0]['data'] ?? []);
        Assert::assertNotEmpty($data, 'The stats should not be empty');
    }

    public function testGetEmailsToSendWinnerVariantReturnsOnlyEligibleEmails(): void
    {
        [$eligibleParent]       = $this->createVariantPair('eligible', 90, 2);
        [$defaultWeightParent]  = $this->createVariantPair('default-weight', 100, 2);
        [$noDelayParent]        = $this->createVariantPair('no-delay', 90, 0);

        $this->em->flush();

        $ids = array_map(
            static fn (Email $email): int => (int) $email->getId(),
            $this->emailModel->getEmailsToSendWinnerVariant()
        );

        sort($ids);

        Assert::assertSame([(int) $eligibleParent->getId()], $ids);
        Assert::assertNotContains((int) $defaultWeightParent->getId(), $ids);
        Assert::assertNotContains((int) $noDelayParent->getId(), $ids);
    }

    public function testTimeLeftToDetermineWinnerReturnsFullDelayWithoutStatsAndVariantStartDate(): void
    {
        [$parent] = $this->createVariantPair('no-start-date', 90, 3);
        $parent->setVariantStartDate(null);
        $this->em->persist($parent);
        $this->em->flush();

        Assert::assertSame(['hours' => 3, 'minutes' => 0], $this->emailModel->timeLeftToDetermineWinner((int) $parent->getId(), 3));
    }

    public function testIsReadyToSendWinnerDependsOnLastSentDate(): void
    {
        [$parent, $winner] = $this->createVariantPair('ready-check', 90, 1);
        $contact           = $this->createContact();

        $stat = new Stat();
        $stat->setEmail($winner);
        $stat->setLead($contact);
        $stat->setEmailAddress($contact->getEmail());
        $stat->setDateSent(new \DateTime('-3 hours', new \DateTimeZone('UTC')));
        $this->em->persist($stat);
        $this->em->flush();

        Assert::assertTrue($this->emailModel->isReadyToSendWinner((int) $parent->getId(), 1));

        [$freshParent] = $this->createVariantPair('not-ready', 90, 1);
        $this->em->flush();

        Assert::assertFalse($this->emailModel->isReadyToSendWinner((int) $freshParent->getId(), 1));
    }

    public function testConvertWinnerVariantCopiesPublishSettingsFromOldParent(): void
    {
        [$parent, $winner] = $this->createVariantPair('conversion', 90, 1);
        $parent->setPublishUp(new \DateTime('2026-01-01 00:00:00', new \DateTimeZone('UTC')));
        $parent->setPublishDown(new \DateTime('2026-01-31 23:59:59', new \DateTimeZone('UTC')));
        $parent->setContinueSending(true);
        $this->em->persist($parent);
        $this->em->flush();

        $this->emailModel->convertWinnerVariant($winner);
        $this->em->flush();
        $this->em->clear();

        /** @var Email $winnerReloaded */
        $winnerReloaded = $this->em->getRepository(Email::class)->find($winner->getId());

        Assert::assertInstanceOf(Email::class, $winnerReloaded);
        Assert::assertNull($winnerReloaded->getVariantParent());
        Assert::assertSame('2026-01-01 00:00:00', $winnerReloaded->getPublishUp()?->format('Y-m-d H:i:s'));
        Assert::assertSame('2026-01-31 23:59:59', $winnerReloaded->getPublishDown()?->format('Y-m-d H:i:s'));
        Assert::assertTrue($winnerReloaded->getContinueSending());
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

    /**
     * @return array{0: Email, 1: Email}
     */
    private function createVariantPair(string $suffix, int $totalWeight, int $sendWinnerDelay): array
    {
        $parent = new Email();
        $parent->setName('Parent '.$suffix);
        $parent->setSubject('Parent '.$suffix);
        $parent->setCustomHTML('parent-'.$suffix);
        $parent->setEmailType('template');
        $parent->setLanguage('en');
        $parent->setIsPublished(true);
        $parent->setContinueSending(true);
        $parent->setVariantSettings([
            'enableAbTest'    => true,
            'totalWeight'     => $totalWeight,
            'sendWinnerDelay' => $sendWinnerDelay,
            'winnerCriteria'  => 'email.openrate',
        ]);
        $this->em->persist($parent);

        $winner = new Email();
        $winner->setName('Winner '.$suffix);
        $winner->setSubject('Winner '.$suffix);
        $winner->setCustomHTML('winner-'.$suffix);
        $winner->setEmailType('template');
        $winner->setLanguage('en');
        $winner->setIsPublished(true);
        $winner->setVariantParent($parent);
        $winner->setVariantSettings([
            'weight'         => 90,
            'winnerCriteria' => 'email.openrate',
        ]);
        $parent->addVariantChild($winner);

        $this->em->persist($winner);

        return [$parent, $winner];
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
        $this->assertInstanceOf(MessageQueueRepository::class, $messageQueueRepository);

        Assert::assertSame(0, $messageQueueRepository->count([]), 'Email should not be postponed.');
    }

    private function assertEmailIsPostponed(Email $email, Lead $contact): void
    {
        $messageQueueRepository = $this->em->getRepository(MessageQueue::class);
        $this->assertInstanceOf(MessageQueueRepository::class, $messageQueueRepository);

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
