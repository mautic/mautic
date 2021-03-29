<?php

declare(strict_types=1);

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\Model;

use Doctrine\ORM\EntityManager;
use Mautic\ChannelBundle\Entity\MessageRepository;
use Mautic\ChannelBundle\Model\MessageQueueModel;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Helper\CacheStorageHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Helper\ThemeHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\EmailRepository;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Entity\StatDevice;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\EmailBundle\Helper\StatsCollectionHelper;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\EmailBundle\Model\SendEmailToContact;
use Mautic\EmailBundle\MonitoredEmail\Mailbox;
use Mautic\EmailBundle\Stat\StatHelper;
use Mautic\LeadBundle\Entity\CompanyRepository;
use Mautic\LeadBundle\Entity\FrequencyRuleRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadDevice;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\DoNotContact;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\LeadBundle\Tracker\DeviceTracker;
use Mautic\PageBundle\Entity\RedirectRepository;
use Mautic\PageBundle\Model\TrackableModel;
use Mautic\UserBundle\Model\UserModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;

class EmailModelTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|IpLookupHelper
     */
    private $ipLookupHelper;

    /**
     * @var MockObject|ThemeHelper
     */
    private $themeHelper;

    /**
     * @var MockObject|Mailbox
     */
    private $mailboxHelper;

    /**
     * @var MockObject|MailHelper
     */
    private $mailHelper;

    /**
     * @var MockObject|LeadModel
     */
    private $leadModel;

    /**
     * @var MockObject|TrackableModel
     */
    private $trackableModel;

    /**
     * @var MockObject|UserModel
     */
    private $userModel;

    /**
     * @var MockObject|Translator
     */
    private $translator;

    /**
     * @var MockObject|Email
     */
    private $emailEntity;

    /**
     * @var MockObject|EntityManager
     */
    private $entityManager;

    /**
     * @var MockObject|StatRepository
     */
    private $statRepository;

    /**
     * @var MockObject|EmailRepository
     */
    private $emailRepository;

    /**
     * @var MockObject|FrequencyRuleRepository
     */
    private $frequencyRepository;

    /**
     * @var MockObject|MessageQueueModel
     */
    private $messageModel;

    /**
     * @var MockObject|CompanyModel
     */
    private $companyModel;

    /**
     * @var MockObject|CompanyRepository
     */
    private $companyRepository;

    /**
     * @var MockObject|DoNotContact
     */
    private $dncModel;

    /**
     * @var StatHelper
     */
    private $statHelper;

    /**
     * @var SendEmailToContact
     */
    private $sendToContactModel;

    /**
     * @var MockObject|DeviceTracker
     */
    private $deviceTrackerMock;

    /**
     * @var MockObject|RedirectRepository
     */
    private $redirectRepositoryMock;

    /**
     * @var MockObject|CacheStorageHelper
     */
    private $cacheStorageHelperMock;

    /**
     * @var MockObject|ContactTracker
     */
    private $contactTracker;

    /**
     * @var EmailModel
     */
    private $emailModel;

    /**
     * @var MockObject|DoNotContact
     */
    private $doNotContact;

    /**
     * @var CorePermissions|MockObject
     */
    private $corePermissions;

    /**
     * @var StatsCollectionHelper|MockObject
     */
    private $statsCollectionHelper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ipLookupHelper           = $this->createMock(IpLookupHelper::class);
        $this->themeHelper              = $this->createMock(ThemeHelper::class);
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
        $this->statsCollectionHelper    = $this->createMock(StatsCollectionHelper::class);
        $this->corePermissions          = $this->createMock(CorePermissions::class);

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
            $this->statsCollectionHelper,
            $this->corePermissions
        );

        $this->emailModel->setTranslator($this->translator);
        $this->emailModel->setEntityManager($this->entityManager);
    }

    /**
     * Test that an array of contacts are sent emails according to A/B test weights.
     */
    public function testVariantEmailWeightsAreAppropriateForMultipleContacts(): void
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
    public function testVariantEmailWeightsAreAppropriateForMultipleContactsSentOneAtATime(): void
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
    public function testDoNotContactIsHonored(): void
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
    public function testFrequencyRulesAreAppliedAndMessageGetsQueued(): void
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
            $this->statsCollectionHelper,
            $this->corePermissions
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

    public function testHitEmailSavesEmailStatAndDeviceStatInTwoTransactions(): void
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

        $this->entityManager->expects($this->exactly(2))
            ->method('persist')
            ->withConsecutive(
                [
                    $this->callback(function ($statDevice) {
                        $this->assertInstanceOf(Stat::class, $statDevice);

                        return true;
                    }),
                ],
                [
                    $this->callback(function ($statDevice) use ($stat, $ipAddress) {
                        $this->assertInstanceOf(StatDevice::class, $statDevice);
                        $this->assertSame($stat, $statDevice->getStat());
                        $this->assertSame($ipAddress, $statDevice->getIpAddress());

                        return true;
                    }),
                ]
            );

        $this->entityManager->expects($this->exactly(2))
            ->method('flush');

        $this->emailModel->setDispatcher($this->createMock(EventDispatcher::class));

        $this->emailModel->hitEmail($stat, $request);
    }
}
