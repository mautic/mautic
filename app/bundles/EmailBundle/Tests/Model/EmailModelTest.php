<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Mautic\ChannelBundle\Entity\MessageRepository;
use Mautic\ChannelBundle\Model\MessageQueueModel;
use Mautic\CoreBundle\Doctrine\Provider\GeneratedColumnsProviderInterface;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Helper\CacheStorageHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Helper\ThemeHelperInterface;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\EmailRepository;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Entity\StatDevice;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\EmailBundle\Model\SendEmailToContact;
use Mautic\EmailBundle\MonitoredEmail\Mailbox;
use Mautic\EmailBundle\Stat\StatHelper;
use Mautic\LeadBundle\Entity\CompanyRepository;
use Mautic\LeadBundle\Entity\DoNotContactRepository;
use Mautic\LeadBundle\Entity\FrequencyRuleRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadDevice;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\DoNotContact;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\LeadBundle\Tracker\DeviceTracker;
use Mautic\PageBundle\Entity\RedirectRepository;
use Mautic\PageBundle\Entity\TrackableRepository;
use Mautic\PageBundle\Model\TrackableModel;
use Mautic\UserBundle\Model\UserModel;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;

class EmailModelTest extends \PHPUnit\Framework\TestCase
{
    const SEGMENT_A = 'segment A';

    const SEGMENT_B = 'segment B';

    private $ipLookupHelper;
    private $themeHelper;
    private $mailboxHelper;
    private $mailHelper;
    private $leadModel;
    private $trackableModel;
    private $userModel;
    private $translator;
    private $emailEntity;
    private $entityManager;
    private $statRepository;
    private $emailRepository;
    private $frequencyRepository;
    private $messageModel;
    private $companyModel;
    private $companyRepository;
    private $dncModel;
    private $statHelper;
    private $sendToContactModel;
    private $deviceTrackerMock;
    private $redirectRepositoryMock;
    private $cacheStorageHelperMock;
    private $contactTracker;
    private $emailModel;
    private $generatedColumnsProvider;
    private $doNotContact;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ipLookupHelper           = $this->createMock(IpLookupHelper::class);
        $this->themeHelper              = $this->createMock(ThemeHelperInterface::class);
        $this->mailboxHelper            = $this->createMock(Mailbox::class);
        $this->mailHelper               = $this->createMock(MailHelper::class);
        $this->leadModel                = $this->createMock(LeadModel::class);
        $this->trackableModel           = $this->createMock(TrackableModel::class);
        $this->userModel                = $this->createMock(UserModel::class);
        $this->translator               = $this->createMock(Translator::class);
        $this->emailEntity              = $this->createMock(Email::class);
        $this->entityManager            = $this->createMock(EntityManager::class);
        $this->statRepository           = $this->createMock(StatRepository::class);
        $this->emailRepository          = $this->createMock(EmailRepository::class);
        $this->frequencyRepository      = $this->createMock(FrequencyRuleRepository::class);
        $this->messageModel             = $this->createMock(MessageQueueModel::class);
        $this->companyModel             = $this->createMock(CompanyModel::class);
        $this->companyRepository        = $this->createMock(CompanyRepository::class);
        $this->dncModel                 = $this->createMock(DoNotContact::class);
        $this->statHelper               = new StatHelper($this->statRepository);
        $this->sendToContactModel       = new SendEmailToContact($this->mailHelper, $this->statHelper, $this->dncModel, $this->translator);
        $this->deviceTrackerMock        = $this->createMock(DeviceTracker::class);
        $this->redirectRepositoryMock   = $this->createMock(RedirectRepository::class);
        $this->cacheStorageHelperMock   = $this->createMock(CacheStorageHelper::class);
        $this->contactTracker           = $this->createMock(ContactTracker::class);
        $this->doNotContact             = $this->createMock(DoNotContact::class);
        $this->generatedColumnsProvider = $this->createMock(GeneratedColumnsProviderInterface::class);

        $this->emailModel = new EmailModel(
            $this->ipLookupHelper,
            $this->themeHelper,
            $this->mailboxHelper,
            $this->mailHelper,
            $this->leadModel,
            $this->companyModel,
            $this->trackableModel,
            $this->userModel,
            $this->messageModel,
            $this->sendToContactModel,
            $this->deviceTrackerMock,
            $this->redirectRepositoryMock,
            $this->cacheStorageHelperMock,
            $this->contactTracker,
            $this->doNotContact,
            $this->generatedColumnsProvider
        );

        $this->emailModel->setTranslator($this->translator);
        $this->emailModel->setEntityManager($this->entityManager);
    }

    /**
     * Test that an array of contacts are sent emails according to A/B test weights.
     */
    public function testVariantEmailWeightsAreAppropriateForMultipleContacts()
    {
        $this->mailHelper->method('getMailer')->will($this->returnValue($this->mailHelper));
        $this->mailHelper->method('flushQueue')->will($this->returnValue(true));
        $this->mailHelper->method('addTo')->will($this->returnValue(true));
        $this->mailHelper->method('queue')->will($this->returnValue([true, []]));
        $this->mailHelper->method('setEmail')->will($this->returnValue(true));
        $this->translator->expects($this->any())
            ->method('hasId')
            ->will($this->returnValue(false));

        // Setup an email variant email
        $variantDate = new \DateTime();
        $this->emailEntity->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1));
        $this->emailEntity->method('getTemplate')
            ->will($this->returnValue(''));
        $this->emailEntity->method('getSentCount')
            ->will($this->returnValue(0));
        $this->emailEntity->method('getVariantSentCount')
            ->will($this->returnValue(0));
        $this->emailEntity->method('getVariantStartDate')
            ->will($this->returnValue($variantDate));
        $this->emailEntity->method('getTranslations')
            ->will($this->returnValue([]));
        $this->emailEntity->method('isPublished')
            ->will($this->returnValue(true));
        $this->emailEntity->method('isVariant')
            ->will($this->returnValue(true));

        $this->mailHelper->method('createEmailStat')
            ->will($this->returnCallback(function () {
                $stat = new Stat();
                $stat->setEmail($this->emailEntity);

                return $stat;
            }
        ));

        $variantA = $this->createMock(Email::class);
        $variantA->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(2));
        $variantA->method('getTemplate')
            ->will($this->returnValue(''));
        $variantA->method('getSentCount')
            ->will($this->returnValue(0));
        $variantA->method('getVariantSentCount')
            ->will($this->returnValue(0));
        $variantA->method('getVariantStartDate')
            ->will($this->returnValue($variantDate));
        $variantA->method('getTranslations')
            ->will($this->returnValue([]));
        $variantA->method('isPublished')
            ->will($this->returnValue(true));
        $variantA->method('isVariant')
            ->will($this->returnValue(true));
        $variantA->method('getVariantSettings')
            ->will($this->returnValue(['weight' => '25']));

        $variantB = $this->createMock(Email::class);
        $variantB->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(3));
        $variantB->method('getTemplate')
            ->will($this->returnValue(''));
        $variantB->method('getSentCount')
            ->will($this->returnValue(0));
        $variantB->method('getVariantSentCount')
            ->will($this->returnValue(0));
        $variantB->method('getVariantStartDate')
            ->will($this->returnValue($variantDate));
        $variantB->method('getTranslations')
            ->will($this->returnValue([]));
        $variantB->method('isPublished')
            ->will($this->returnValue(true));
        $variantB->method('isVariant')
            ->will($this->returnValue(true));
        $variantB->method('getVariantSettings')
            ->will($this->returnValue(['weight' => '25']));

        $this->emailEntity->method('getVariantChildren')
            ->will($this->returnValue([$variantA, $variantB]));

        $this->emailRepository->method('getDoNotEmailList')
            ->will($this->returnValue([]));

        $this->frequencyRepository->method('getAppliedFrequencyRules')
            ->will($this->returnValue([]));

        $this->entityManager->expects($this->any())
            ->method('getRepository')
            ->will(
                $this->returnValueMap(
                    [
                        ['MauticLeadBundle:FrequencyRule', $this->frequencyRepository],
                        ['MauticEmailBundle:Email', $this->emailRepository],
                        ['MauticEmailBundle:Stat', $this->statRepository],
                    ]
                )
            );

        $this->companyRepository->method('getCompaniesForContacts')
            ->will($this->returnValue([]));

        $this->companyModel->method('getRepository')
            ->willReturn($this->companyRepository);

        $count    = 12;
        $contacts = [];
        while ($count > 0) {
            $contacts[] = [
                'id'        => $count,
                'email'     => "email{$count}@domain.com",
                'firstname' => "firstname{$count}",
                'lastname'  => "lastname{$count}",
            ];
            --$count;
        }

        $this->emailModel->sendEmail($this->emailEntity, $contacts);

        $emailSettings = $this->emailModel->getEmailSettings($this->emailEntity);

        // Sent counts should be as follows
        // ID 1 => 6 50%
        // ID 2 => 3 25%
        // ID 3 => 3 25%

        $counts = [];
        foreach ($emailSettings as $id => $details) {
            $counts[] = "$id:{$details['variantCount']}";
        }
        $counts = implode('; ', $counts);

        $this->assertEquals(6, $emailSettings[1]['variantCount'], $counts);
        $this->assertEquals(3, $emailSettings[2]['variantCount'], $counts);
        $this->assertEquals(3, $emailSettings[3]['variantCount'], $counts);
    }

    /**
     * Test that sending emails to contacts one at a time are according to A/B test weights.
     */
    public function testVariantEmailWeightsAreAppropriateForMultipleContactsSentOneAtATime()
    {
        $this->mailHelper->method('getMailer')->will($this->returnValue($this->mailHelper));
        $this->mailHelper->method('flushQueue')->will($this->returnValue(true));
        $this->mailHelper->method('addTo')->will($this->returnValue(true));
        $this->mailHelper->method('queue')->will($this->returnValue([true, []]));
        $this->mailHelper->method('setEmail')->will($this->returnValue(true));
        $this->translator->expects($this->any())
            ->method('hasId')
            ->will($this->returnValue(false));

        // Setup an email variant email
        $variantDate = new \DateTime();
        $this->emailEntity->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1));
        $this->emailEntity->method('getTemplate')->will($this->returnValue(''));
        $this->emailEntity->method('getSentCount')->will($this->returnValue(0));
        $this->emailEntity->method('getVariantSentCount')->will($this->returnValue(0));
        $this->emailEntity->method('getVariantStartDate')->will($this->returnValue($variantDate));
        $this->emailEntity->method('getTranslations')->will($this->returnValue([]));
        $this->emailEntity->method('isPublished')->will($this->returnValue(true));
        $this->emailEntity->method('isVariant')->will($this->returnValue(true));

        $this->mailHelper->method('createEmailStat')
            ->will($this->returnCallback(function () {
                $stat = new Stat();
                $stat->setEmail($this->emailEntity);

                return $stat;
            }
            ));

        $variantA = $this->createMock(Email::class);
        $variantA->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(2));
        $variantA->method('getTemplate')
            ->will($this->returnValue(''));
        $variantA->method('getSentCount')
            ->will($this->returnValue(0));
        $variantA->method('getVariantSentCount')
            ->will($this->returnValue(0));
        $variantA->method('getVariantStartDate')
            ->will($this->returnValue($variantDate));
        $variantA->method('getTranslations')
            ->will($this->returnValue([]));
        $variantA->method('isPublished')
            ->will($this->returnValue(true));
        $variantA->method('isVariant')
            ->will($this->returnValue(true));
        $variantA->method('getVariantSettings')
            ->will($this->returnValue(['weight' => '25']));

        $variantB = $this->createMock(Email::class);
        $variantB->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(3));
        $variantB->method('getTemplate')
            ->will($this->returnValue(''));
        $variantB->method('getSentCount')
            ->will($this->returnValue(0));
        $variantB->method('getVariantSentCount')
            ->will($this->returnValue(0));
        $variantB->method('getVariantStartDate')
            ->will($this->returnValue($variantDate));
        $variantB->method('getTranslations')
            ->will($this->returnValue([]));
        $variantB->method('isPublished')
            ->will($this->returnValue(true));
        $variantB->method('isVariant')
            ->will($this->returnValue(true));
        $variantB->method('getVariantSettings')
            ->will($this->returnValue(['weight' => '25']));

        $this->emailEntity->method('getVariantChildren')
            ->will($this->returnValue([$variantA, $variantB]));

        $this->emailRepository->method('getDoNotEmailList')
            ->will($this->returnValue([]));

        $this->frequencyRepository->method('getAppliedFrequencyRules')
            ->will($this->returnValue([]));

        $this->entityManager->expects($this->any())
            ->method('getRepository')
            ->will(
                $this->returnValueMap(
                    [
                        ['MauticLeadBundle:FrequencyRule', $this->frequencyRepository],
                        ['MauticEmailBundle:Email', $this->emailRepository],
                        ['MauticEmailBundle:Stat', $this->statRepository],
                    ]
                )
            );

        $this->companyRepository->method('getCompaniesForContacts')
            ->will($this->returnValue([]));

        $this->companyModel->method('getRepository')
            ->willReturn($this->companyRepository);

        $count   = 12;
        $results = [];
        while ($count > 0) {
            $contact = [
                'id'        => $count,
                'email'     => "email{$count}@domain.com",
                'firstname' => "firstname{$count}",
                'lastname'  => "lastname{$count}",
            ];
            --$count;

            $results[] = $this->emailModel->sendEmail($this->emailEntity, [$contact]);
        }

        $emailSettings = $this->emailModel->getEmailSettings($this->emailEntity);

        // Sent counts should be as follows
        // ID 1 => 6 50%
        // ID 2 => 3 25%
        // ID 3 => 3 25%

        $counts = [];
        foreach ($emailSettings as $id => $details) {
            $counts[] = "$id:{$details['variantCount']}";
        }
        $counts = implode('; ', $counts);

        $this->assertEquals(6, $emailSettings[1]['variantCount'], $counts);
        $this->assertEquals(3, $emailSettings[2]['variantCount'], $counts);
        $this->assertEquals(3, $emailSettings[3]['variantCount'], $counts);
    }

    /**
     * Test that DoNotContact is honored.
     */
    public function testDoNotContactIsHonored()
    {
        $this->translator->expects($this->any())
            ->method('hasId')
            ->will($this->returnValue(false));

        $this->emailRepository->method('getDoNotEmailList')
            ->will($this->returnValue([1 => 'someone@domain.com']));

        $this->entityManager->expects($this->any())
            ->method('getRepository')
            ->will(
                $this->returnValueMap(
                    [
                        ['MauticEmailBundle:Email', $this->emailRepository],
                        ['MauticEmailBundle:Stat', $this->statRepository],
                        ['MauticLeadBundle:FrequencyRule', $this->frequencyRepository],
                    ]
                )
            );

        // If it makes it to the point of calling getContactCompanies then DNC failed
        $this->companyModel->expects($this->exactly(0))
            ->method('getRepository');

        $this->emailEntity->method('getId')
            ->will($this->returnValue(1));

        $this->assertTrue(0 === count($this->emailModel->sendEmail($this->emailEntity, [1 => ['id' => 1, 'email' => 'someone@domain.com']])));
    }

    /**
     * Test that message is queued for a frequency rule value.
     */
    public function testFrequencyRulesAreAppliedAndMessageGetsQueued()
    {
        $this->translator->expects($this->any())
            ->method('hasId')
            ->will($this->returnValue(false));

        $this->emailRepository->method('getDoNotEmailList')
            ->will($this->returnValue([]));
        $this->frequencyRepository->method('getAppliedFrequencyRules')
            ->will($this->returnValue([['lead_id' => 1, 'frequency_number' => 1, 'frequency_time' => 'DAY']]));

        $this->entityManager->expects($this->any())
            ->method('getRepository')
            ->will(
                $this->returnValueMap(
                    [
                        ['MauticEmailBundle:Email', $this->emailRepository],
                        ['MauticEmailBundle:Stat', $this->statRepository],
                        ['MauticLeadBundle:FrequencyRule', $this->frequencyRepository],
                        ['MauticChannelBundle:MessageQueue', $this->createMock(MessageRepository::class)],
                    ]
                )
            );
        $leadEntity = (new Lead())
            ->setEmail('someone@domain.com');

        $this->entityManager->expects($this->any())
            ->method('getReference')
            ->will(
                $this->returnValue($leadEntity)
            );

        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);

        $messageModel = new MessageQueueModel($this->leadModel, $this->companyModel, $coreParametersHelper);
        $messageModel->setEntityManager($this->entityManager);
        $messageModel->setUserHelper($this->createMock(UserHelper::class));
        $messageModel->setDispatcher($this->createMock(EventDispatcher::class));

        $emailModel = new EmailModel(
            $this->ipLookupHelper,
            $this->themeHelper,
            $this->mailboxHelper,
            $this->mailHelper,
            $this->leadModel,
            $this->companyModel,
            $this->trackableModel,
            $this->userModel,
            $messageModel,
            $this->sendToContactModel,
            $this->deviceTrackerMock,
            $this->redirectRepositoryMock,
            $this->cacheStorageHelperMock,
            $this->contactTracker,
            $this->doNotContact,
            $this->generatedColumnsProvider
        );

        $emailModel->setTranslator($this->translator);
        $emailModel->setEntityManager($this->entityManager);

        $this->emailEntity->method('getId')
            ->will($this->returnValue(1));

        $result = $emailModel->sendEmail(
            $this->emailEntity,
            [
                1 => [
                    'id'        => 1,
                    'email'     => 'someone@domain.com',
                    'firstname' => 'someone',
                    'lastname'  => 'someone',
                ],
            ],
            ['email_type' => 'marketing']
        );
        $this->assertTrue(0 === count($result), print_r($result, true));
    }

    public function testHitEmailSavesEmailStatAndDeviceStatInTwoTransactions()
    {
        $contact       = new Lead();
        $stat          = new Stat();
        $request       = new Request();
        $contactDevice = new LeadDevice();
        $ipAddress     = new IpAddress();

        $stat->setLead($contact);

        $this->ipLookupHelper->expects($this->once())
            ->method('getIpAddress')
            ->willReturn($ipAddress);

        $this->deviceTrackerMock->expects($this->once())
            ->method('createDeviceFromUserAgent')
            ->with($contact)
            ->willReturn($contactDevice);

        $this->entityManager->expects($this->at(0))
            ->method('persist')
            ->with($this->callback(function ($statDevice) {
                $this->assertInstanceOf(Stat::class, $statDevice);

                return true;
            }));

        $this->entityManager->expects($this->at(1))
            ->method('flush');

        $this->entityManager->expects($this->at(2))
            ->method('persist')
            ->with($this->callback(function ($statDevice) use ($stat, $ipAddress) {
                $this->assertInstanceOf(StatDevice::class, $statDevice);
                $this->assertSame($stat, $statDevice->getStat());
                $this->assertSame($ipAddress, $statDevice->getIpAddress());

                return true;
            }));

        $this->entityManager->expects($this->at(3))
            ->method('flush');

        $this->emailModel->setDispatcher($this->createMock(EventDispatcher::class));

        $this->emailModel->hitEmail($stat, $request);
    }

    public function testGetEmailListStatsOneSegment()
    {
        $list = $this->createMock(LeadList::class);
        $list->method('getName')->willReturn(self::SEGMENT_A);

        $lists = new ArrayCollection([$list]);

        $result = $this->getEmailListStats($lists);

        self::assertCount(1, $result['datasets']);
        self::assertEquals(self::SEGMENT_A, $result['datasets'][0]['label']);
    }

    public function testGetEmailListStatsTwoSegments()
    {
        $list = $this->createMock(LeadList::class);
        $list->method('getName')->willReturn(self::SEGMENT_A);

        $list2 = $this->createMock(LeadList::class);
        $list2->method('getName')->willReturn(self::SEGMENT_B);

        $lists = new ArrayCollection([$list, $list2]);

        $result = $this->getEmailListStats($lists);

        self::assertCount(3, $result['datasets']);
        self::assertEquals(self::SEGMENT_A, $result['datasets'][1]['label']);
        self::assertEquals(self::SEGMENT_B, $result['datasets'][2]['label']);
    }

    private function getEmailListStats(ArrayCollection $lists)
    {
        $trackableRepo    = $this->createMock(TrackableRepository::class);
        $doNotContactRepo = $this->createMock(DoNotContactRepository::class);

        $this->entityManager->expects($this->any())
            ->method('getRepository')
            ->will(
                $this->returnValueMap(
                    [
                        ['MauticEmailBundle:Stat', $this->statRepository],
                        ['MauticLeadBundle:DoNotContact', $doNotContactRepo],
                        ['MauticPageBundle:Trackable', $trackableRepo],
                    ]
                )
            );

        $this->emailEntity->method('getLists')->willReturn($lists);

        $connection   = $this->createMock(Connection::class);
        $this->entityManager->method('getConnection')->willReturn($connection);

        $dateFromObject = new \DateTime('now');
        $dateToObject   = new \DateTime('-1 month');

        $this->emailEntity->method('getLists')->willReturn($lists);

        return $this->emailModel->getEmailListStats($this->emailEntity, true, $dateFromObject, $dateToObject);
    }
}
