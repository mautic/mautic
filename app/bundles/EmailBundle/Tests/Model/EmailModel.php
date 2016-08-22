<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Helper\ThemeHelper;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\EmailBundle\MonitoredEmail\Mailbox;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PageBundle\Model\TrackableModel;
use Mautic\UserBundle\Model\UserModel;

class EmailModel extends \PHPUnit_Framework_TestCase
{
    /**
     * Test that processMailerCallback handles an array of emails correctly
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
                        ['notfound@nowhere.com', true, null]
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
                        ['MauticEmailBundle:Stat', $statRepository]
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
                    'notfound@nowhere.com' => 'some reason'
                ]
            ]
        ];

        $dnc = $emailModel->processMailerCallback($response);

        $this->assertCount(1, $dnc);
    }

    /**
     * Test that processMailerCallback handles an array of hashIds correctly
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
                        ['MauticEmailBundle:Stat', $statRepository]
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
                    '123xyz' => 'some reason' // not found
                ]
            ]
        ];

        $dnc = $emailModel->processMailerCallback($response);

        $this->assertCount(1, $dnc);
    }
}