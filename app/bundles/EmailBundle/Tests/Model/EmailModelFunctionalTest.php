<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Model;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListLead;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Entity\Redirect;
use Mautic\PageBundle\Entity\Trackable;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EmailModelFunctionalTest extends MauticMysqlTestCase
{
    use CreateTestEntitiesTrait;

    private EmailModel|ContainerInterface $emailModel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->emailModel = static::getContainer()->get('mautic.email.model.email');
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

        $emailModel = static::getContainer()->get('mautic.email.model.email');
        \assert($emailModel instanceof EmailModel);
        [$sentCount] = $this->emailModel->sendEmailToLists($email, [$segment], null, null, null, null, null, 3, 1);
        $this->assertEquals($sentCount, 7);
        [$sentCount] = $this->emailModel->sendEmailToLists($email, [$segment], null, null, null, null, null, 3, 2);
        $this->assertEquals($sentCount, 8);
        [$sentCount] = $this->emailModel->sendEmailToLists($email, [$segment], null, null, null, null, null, 3, 3);
        $this->assertEquals($sentCount, 8);
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
        $this->assertEquals('Clicked', $result['datasets'][3]['label']);
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

        $emailModel                                             =  static::getContainer()->get('mautic.email.model.email');
        [$sentCount, $failedCount, $failedRecipientsByList]     = $this->emailModel->sendEmailToLists($email, [$segment], 4, 2);
        $this->assertEquals($sentCount, 4);
        [$sentCount, $failedCount, $failedRecipientsByList] = $this->emailModel->sendEmailToLists($email, [$segment], 3, 2);
        $this->assertEquals($sentCount, 3);
        [$sentCount, $failedCount, $failedRecipientsByList] = $this->emailModel->sendEmailToLists($email, [$segment], 2);
        $this->assertEquals($sentCount, 2);
        [$sentCount, $failedCount, $failedRecipientsByList] = $this->emailModel->sendEmailToLists($email, [$segment], 4);
        $this->assertEquals($sentCount, 1);

        $email                                                  = $this->createEmail($segment);
        [$sentCount, $failedCount, $failedRecipientsByList]     = $this->emailModel->sendEmailToLists($email, [$segment]);
        $this->assertEquals($sentCount, 10);

        $email                                                  = $this->createEmail($segment);
        [$sentCount, $failedCount, $failedRecipientsByList]     = $this->emailModel->sendEmailToLists($email, [$segment], null, 2);
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

        $emailModel = static::getContainer()->get('mautic.email.model.email');
        \assert($emailModel instanceof EmailModel);
        $parentEmail->setName('Test change');
        $emailModel->saveEntity($parentEmail);

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

    private function createDnc(string $channel, Lead $contact, int $reason, int $channelId = null): DoNotContact
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
        /** @var EmailModel $emailModel */
        $emailModel   = $this->getContainer()->get('mautic.email.model.email');
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
        $results = $emailModel->getCountryStats($email, $dateFrom, $dateTo);

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
        /** @var EmailModel $emailModel */
        $emailModel   = $this->getContainer()->get('mautic.email.model.email');

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
}
