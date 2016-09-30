<?php
/**
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Helper\ThemeHelper;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\EmailRepository;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\EmailBundle\MonitoredEmail\Mailbox;
use Mautic\LeadBundle\Entity\FrequencyRuleRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PageBundle\Model\TrackableModel;
use Mautic\UserBundle\Model\UserModel;

class EmailModelTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test that an array of contacts are sent emails according to A/B test weights.
     */
    public function testVariantEmailWeightsAreAppropriateForMultipleContacts()
    {
        // Setup dependencies
        $ipLookupHelper = $this->getMockBuilder(IpLookupHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $themeHelper = $this->getMockBuilder(ThemeHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mailboxHelper = $this->getMockBuilder(Mailbox::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mailHelper = $this->getMockBuilder(MailHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mailHelper->method('getMailer')
            ->will($this->returnValue($mailHelper));
        $mailHelper->method('flushQueue')
            ->will($this->returnValue(true));
        $mailHelper->method('addTo')
            ->will($this->returnValue(true));
        $mailHelper->method('queue')
            ->will($this->returnValue(true));

        $leadModel = $this->getMockBuilder(LeadModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $trackableModel = $this->getMockBuilder(TrackableModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $userModel = $this->getMockBuilder(UserModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $coreParametersHelper = $this->getMockBuilder(CoreParametersHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Setup the translator
        $translator = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $translator->expects($this->any())
            ->method('has')
            ->will($this->returnValue(false));

        // Setup an email variant email
        $variantDate = new \DateTime();
        $emailEntity = $this->getMockBuilder(Email::class)
            ->disableOriginalConstructor()
            ->getMock();
        $emailEntity->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1));
        $emailEntity->method('getTemplate')
            ->will($this->returnValue(''));
        $emailEntity->method('getSentCount')
            ->will($this->returnValue(0));
        $emailEntity->method('getVariantSentCount')
            ->will($this->returnValue(0));
        $emailEntity->method('getVariantStartDate')
            ->will($this->returnValue($variantDate));
        $emailEntity->method('getTranslations')
            ->will($this->returnValue([]));
        $emailEntity->method('isPublished')
            ->will($this->returnValue(true));
        $emailEntity->method('isVariant')
            ->will($this->returnValue(true));

        $variantA = $this->getMockBuilder(Email::class)
            ->disableOriginalConstructor()
            ->getMock();
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

        $variantB = $this->getMockBuilder(Email::class)
            ->disableOriginalConstructor()
            ->getMock();
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

        $emailEntity->method('getVariantChildren')
            ->will($this->returnValue([$variantA, $variantB]));

        // Setup the EntityManager
        $entityManager = $this
            ->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $statRepository = $this->getMockBuilder(StatRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $emailRepository = $this->getMockBuilder(EmailRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $emailRepository->method('getDoNotEmailList')
            ->will($this->returnValue([]));
        $frequencyRepository = $this->getMockBuilder(FrequencyRuleRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $frequencyRepository->method('getAppliedFrequencyRules')
            ->will($this->returnValue([]));

        $entityManager->expects($this->any())
            ->method('getRepository')
            ->will(
                $this->returnValueMap(
                    [
                        ['MauticLeadBundle:FrequencyRule', $frequencyRepository],
                        ['MauticEmailBundle:Email', $emailRepository],
                        ['MauticEmailBundle:Stat', $statRepository],
                    ]
                )
            );

        $emailModel = new \Mautic\EmailBundle\Model\EmailModel(
            $ipLookupHelper,
            $themeHelper,
            $mailboxHelper,
            $mailHelper,
            $leadModel,
            $trackableModel,
            $userModel,
            $coreParametersHelper
        );

        $emailModel->setTranslator($translator);
        $emailModel->setEntityManager($entityManager);

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

        $emailModel->sendEmail($emailEntity, $contacts);

        $emailSettings = $emailModel->getEmailSettings($emailEntity);

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
        // Setup dependencies
        $ipLookupHelper = $this->getMockBuilder(IpLookupHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $themeHelper = $this->getMockBuilder(ThemeHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mailboxHelper = $this->getMockBuilder(Mailbox::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mailHelper = $this->getMockBuilder(MailHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mailHelper->method('getMailer')
            ->will($this->returnValue($mailHelper));
        $mailHelper->method('flushQueue')
            ->will($this->returnValue(true));
        $mailHelper->method('addTo')
            ->will($this->returnValue(true));
        $mailHelper->method('queue')
            ->will($this->returnValue(true));

        $leadModel = $this->getMockBuilder(LeadModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $trackableModel = $this->getMockBuilder(TrackableModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $userModel = $this->getMockBuilder(UserModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $coreParametersHelper = $this->getMockBuilder(CoreParametersHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Setup the translator
        $translator = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $translator->expects($this->any())
            ->method('has')
            ->will($this->returnValue(false));

        // Setup an email variant email
        $variantDate = new \DateTime();
        $emailEntity = $this->getMockBuilder(Email::class)
            ->disableOriginalConstructor()
            ->getMock();
        $emailEntity->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1));
        $emailEntity->method('getTemplate')
            ->will($this->returnValue(''));
        $emailEntity->method('getSentCount')
            ->will($this->returnValue(0));
        $emailEntity->method('getVariantSentCount')
            ->will($this->returnValue(0));
        $emailEntity->method('getVariantStartDate')
            ->will($this->returnValue($variantDate));
        $emailEntity->method('getTranslations')
            ->will($this->returnValue([]));
        $emailEntity->method('isPublished')
            ->will($this->returnValue(true));
        $emailEntity->method('isVariant')
            ->will($this->returnValue(true));

        $variantA = $this->getMockBuilder(Email::class)
            ->disableOriginalConstructor()
            ->getMock();
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

        $variantB = $this->getMockBuilder(Email::class)
            ->disableOriginalConstructor()
            ->getMock();
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

        $emailEntity->method('getVariantChildren')
            ->will($this->returnValue([$variantA, $variantB]));

        // Setup the EntityManager
        $entityManager = $this
            ->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $statRepository = $this->getMockBuilder(StatRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $emailRepository = $this->getMockBuilder(EmailRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $emailRepository->method('getDoNotEmailList')
            ->will($this->returnValue([]));
        $frequencyRepository = $this->getMockBuilder(FrequencyRuleRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $frequencyRepository->method('getAppliedFrequencyRules')
            ->will($this->returnValue([]));

        $entityManager->expects($this->any())
            ->method('getRepository')
            ->will(
                $this->returnValueMap(
                    [
                        ['MauticLeadBundle:FrequencyRule', $frequencyRepository],
                        ['MauticEmailBundle:Email', $emailRepository],
                        ['MauticEmailBundle:Stat', $statRepository],
                    ]
                )
            );

        $emailModel = new \Mautic\EmailBundle\Model\EmailModel(
            $ipLookupHelper,
            $themeHelper,
            $mailboxHelper,
            $mailHelper,
            $leadModel,
            $trackableModel,
            $userModel,
            $coreParametersHelper
        );

        $emailModel->setTranslator($translator);
        $emailModel->setEntityManager($entityManager);

        $count = 12;
        while ($count > 0) {
            $contact = [
                'id'        => $count,
                'email'     => "email{$count}@domain.com",
                'firstname' => "firstname{$count}",
                'lastname'  => "lastname{$count}",
            ];
            --$count;

            $emailModel->sendEmail($emailEntity, [$contact]);
        }

        $emailSettings = $emailModel->getEmailSettings($emailEntity);

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
     * Test that processMailerCallback handles an array of emails correctly.
     */
    public function testProcessMailerCallbackWithEmails()
    {
        // Setup dependencies
        $ipLookupHelper = $this->getMockBuilder(IpLookupHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $themeHelper = $this->getMockBuilder(ThemeHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mailboxHelper = $this->getMockBuilder(Mailbox::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mailHelper = $this->getMockBuilder(MailHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $leadModel = $this->getMockBuilder(LeadModel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $leadModel->expects($this->once())
            ->method('addDncForLead')
            ->will($this->returnValue(true));

        $trackableModel = $this->getMockBuilder(TrackableModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $userModel = $this->getMockBuilder(UserModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $coreParametersHelper = $this->getMockBuilder(CoreParametersHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Setup the translator
        $translator = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $translator->expects($this->any())
            ->method('has')
            ->will($this->returnValue(false));

        // Setup the StatRepository
        $statRepository = $this->getMockBuilder(StatRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $leadEntity = $this->getMockBuilder(Lead::class)
            ->disableOriginalConstructor()
            ->getMock();
        $leadEntity->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1));

        // Setup the LeadRepository
        $leadRepository = $this->getMockBuilder(LeadRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $leadRepository->expects($this->exactly(2))
            ->method('getLeadByEmail')
            ->will(
                $this->returnValueMap(
                    [
                        ['foo@bar.com', true, 1],
                        ['notfound@nowhere.com', true, null],
                    ]
                )
            );

        // Setup the EntityManager
        $entityManager = $this
            ->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $entityManager->expects($this->any())
            ->method('getRepository')
            ->will(
                $this->returnValueMap(
                    [
                        ['MauticLeadBundle:Lead', $leadRepository],
                        ['MauticEmailBundle:Stat', $statRepository],
                    ]
                )
            );
        $entityManager->expects($this->any())
            ->method('getReference')
            ->will($this->returnValue($leadEntity));

        $emailModel = new \Mautic\EmailBundle\Model\EmailModel(
            $ipLookupHelper,
            $themeHelper,
            $mailboxHelper,
            $mailHelper,
            $leadModel,
            $trackableModel,
            $userModel,
            $coreParametersHelper
        );

        $emailModel->setTranslator($translator);
        $emailModel->setEntityManager($entityManager);

        $response = $response = [
            2 => [
                'emails' => [
                    'foo@bar.com'          => 'some reason',
                    'notfound@nowhere.com' => 'some reason',
                ],
            ],
        ];

        $dnc = $emailModel->processMailerCallback($response);

        $this->assertCount(1, $dnc);
    }

    /**
     * Test that processMailerCallback handles an array of hashIds correctly.
     */
    public function testProcessMailerCallbackWithHashIds()
    {
        // Setup dependencies
        $ipLookupHelper = $this->getMockBuilder(IpLookupHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $themeHelper = $this->getMockBuilder(ThemeHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mailboxHelper = $this->getMockBuilder(Mailbox::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mailHelper = $this->getMockBuilder(MailHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $leadModel = $this->getMockBuilder(LeadModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $trackableModel = $this->getMockBuilder(TrackableModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $userModel = $this->getMockBuilder(UserModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $coreParametersHelper = $this->getMockBuilder(CoreParametersHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Setup the translator
        $translator = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $translator->expects($this->any())
            ->method('has')
            ->will($this->returnValue(false));

        // Setup the StatRepository
        $statRepository = $this->getMockBuilder(StatRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $statRepository->expects($this->once())
            ->method('getTableAlias')
            ->will($this->returnValue('s'));

        $leadEntity = $this->getMockBuilder(Lead::class)
            ->disableOriginalConstructor()
            ->getMock();
        $leadEntity->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1));

        $emailEntity = $this->getMockBuilder(Email::class)
            ->disableOriginalConstructor()
            ->getMock();
        $emailEntity->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1));

        $statEntity = new Stat();
        $statEntity->setTrackingHash('xyz123');
        $statEntity->setLead($leadEntity);
        $statEntity->setEmail($emailEntity);

        $statRepository->expects($this->any())
            ->method('getEntities')
            ->will($this->returnValue([$statEntity]));

        // Setup the EntityManager
        $entityManager = $this
            ->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $entityManager->expects($this->any())
            ->method('getRepository')
            ->will(
                $this->returnValueMap(
                    [
                        ['MauticEmailBundle:Stat', $statRepository],
                    ]
                )
            );
        $entityManager->expects($this->any())
            ->method('getReference')
            ->will($this->returnValue($leadEntity));

        $emailModel = new \Mautic\EmailBundle\Model\EmailModel(
            $ipLookupHelper,
            $themeHelper,
            $mailboxHelper,
            $mailHelper,
            $leadModel,
            $trackableModel,
            $userModel,
            $coreParametersHelper
        );

        $emailModel->setTranslator($translator);
        $emailModel->setEntityManager($entityManager);

        $response = [
            2 => [
                'hashIds' => [
                    'xyz123' => 'some reason',
                    '123xyz' => 'some reason', // not found
                ],
            ],
        ];

        $dnc = $emailModel->processMailerCallback($response);

        $this->assertCount(1, $dnc);
    }
}
