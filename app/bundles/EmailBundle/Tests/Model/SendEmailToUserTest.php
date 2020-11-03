<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\Model;

use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\EmailBundle\Exception\EmailCouldNotBeSentException;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\EmailBundle\Model\SendEmailToUser;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Validator\CustomFieldValidator;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class SendEmailToUserTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|EmailModel
     */
    private $emailModel;

    /**
     * @var MockObject|EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var MockObject|CustomFieldValidator
     */
    private $customFieldValidator;

    /**
     * @var SendEmailToUser
     */
    private $sendEmailToUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->emailModel           = $this->createMock(EmailModel::class);
        $this->dispatcher           = $this->createMock(EventDispatcherInterface::class);
        $this->customFieldValidator = $this->createMock(CustomFieldValidator::class);
        $this->sendEmailToUser      = new SendEmailToUser(
            $this->emailModel,
            $this->dispatcher,
            $this->customFieldValidator
        );
    }

    public function testEmailNotFound()
    {
        $lead = new Lead();

        $this->emailModel->expects($this->once())
            ->method('getEntity')
            ->with(100)
            ->willReturn(null);

        $config                       = [];
        $config['useremail']['email'] = 100;

        $this->expectException(EmailCouldNotBeSentException::class);

        $this->sendEmailToUser->sendEmailToUsers($config, $lead);
    }

    public function testEmailNotPublished()
    {
        $lead  = new Lead();
        $email = new Email();
        $email->setIsPublished(false);

        $this->emailModel->expects($this->once())
            ->method('getEntity')
            ->with(100)
            ->willREturn($email);

        $config                       = [];
        $config['useremail']['email'] = 100;

        $this->expectException(EmailCouldNotBeSentException::class);

        $this->sendEmailToUser->sendEmailToUsers($config, $lead);
    }

    public function testSendEmailWithNoError()
    {
        $lead  = new Lead();
        $owner = new class() extends User {
            public function getId()
            {
                return 10;
            }
        };

        $lead->setOwner($owner);

        $email = new Email();
        $email->setIsPublished(true);

        $this->emailModel->expects($this->once())
            ->method('getEntity')
            ->with(33)
            ->willReturn($email);

        $emailSendEvent                       = new class() extends EmailSendEvent {
            public $getTokenMethodCallCounter = 0;

            public function __construct()
            {
            }

            public function getTokens($includeGlobal = true)
            {
                ++$this->getTokenMethodCallCounter;

                return [];
            }
        };

        // Token for Email
        $this->emailModel->expects($this->once())
            ->method('dispatchEmailSendEvent')
            ->willReturn($emailSendEvent);

        //Send email method

        $this->emailModel
            ->expects($this->once())
            ->method('sendEmailToUser')
            ->will($this->returnCallback(function ($email, $users, $leadCredentials, $tokens, $assetAttachments, $saveStat, $to, $cc, $bcc) {
                $expectedUsers = [
                    ['id' => 6],
                    ['id' => 7],
                    ['id' => 10], // owner ID
                ];
                $this->assertInstanceOf(Email::class, $email);
                $this->assertEquals($expectedUsers, $users);
                $this->assertFalse($saveStat);
                $this->assertEquals(['hello@there.com', 'bob@bobek.cz'], $to);
                $this->assertEquals([], $cc);
                $this->assertEquals(['hidden@translation.in'], $bcc);
            }));

        $config = [
            'useremail' => [
                'email' => 33,
            ],
            'user_id'  => [6, 7],
            'to_owner' => true,
            'to'       => 'hello@there.com, bob@bobek.cz',
            'bcc'      => 'hidden@translation.in',
        ];

        $this->sendEmailToUser->sendEmailToUsers($config, $lead);

        Assert::assertSame(1, $emailSendEvent->getTokenMethodCallCounter);
    }
}
