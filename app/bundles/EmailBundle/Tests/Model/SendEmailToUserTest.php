<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Test\Model;

use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\EmailBundle\Exception\EmailCouldNotBeSentException;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\EmailBundle\Model\SendEmailToUser;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\UserBundle\Entity\User;

class SendEmailToUserTest extends \PHPUnit_Framework_TestCase
{
    public function testEmailNotFound()
    {
        $lead = new Lead();

        $mockEmailModel = $this->getMockBuilder(EmailModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockEmailModel->expects($this->once())
            ->method('getEntity')
            ->with(100)
            ->will($this->returnValue(null));

        $sendEmailToUser = new SendEmailToUser($mockEmailModel);

        $config                       = [];
        $config['useremail']['email'] = 100;

        $this->setExpectedException(EmailCouldNotBeSentException::class);

        $sendEmailToUser->sendEmailToUsers($config, $lead);
    }

    public function testEmailNotPublished()
    {
        $lead = new Lead();

        $email = new Email();
        $email->setIsPublished(false);

        $mockEmailModel = $this->getMockBuilder(EmailModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockEmailModel->expects($this->once())
            ->method('getEntity')
            ->with(100)
            ->will($this->returnValue($email));

        $sendEmailToUser = new SendEmailToUser($mockEmailModel);

        $config                       = [];
        $config['useremail']['email'] = 100;

        $this->setExpectedException(EmailCouldNotBeSentException::class);

        $sendEmailToUser->sendEmailToUsers($config, $lead);
    }

    public function testSendEmailWithNoError()
    {
        $lead = new Lead();

        $mockOwner = $this->getMockBuilder(User::class)
            ->getMock();

        $mockOwner->expects($this->exactly(3))
            ->method('getId')
            ->will($this->returnValue(10));

        $lead->setOwner($mockOwner);

        $email = new Email();
        $email->setIsPublished(true);

        $mockEmailModel = $this->getMockBuilder(EmailModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockEmailModel->expects($this->once())
            ->method('getEntity')
            ->with(33)
            ->will($this->returnValue($email));

        // Token for Email
        $mockEmailSendEvent = $this->getMockBuilder(EmailSendEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockEmailSendEvent->expects($this->once())
            ->method('getTokens')
            ->will($this->returnValue([]));

        $mockEmailModel->expects($this->once())
            ->method('dispatchEmailSendEvent')
            ->will($this->returnValue($mockEmailSendEvent));

        //Send email method

        $mockEmailModel
            ->expects($this->once())
            ->method('sendEmailToUser')
            ->will($this->returnCallback(function ($email, $users, $leadCredentials, $tokens, $assetAttachments, $saveStat, $to, $cc, $bcc) {
                $expectedUsers = [
                    ['id' => 6],
                    ['id' => 7],
                    ['id' => 10], // owner ID
                ];
                \PHPUnit_Framework_Assert::assertTrue($email instanceof Email);
                \PHPUnit_Framework_Assert::assertEquals($expectedUsers, $users);
                \PHPUnit_Framework_Assert::assertFalse($saveStat);
                \PHPUnit_Framework_Assert::assertEquals(['hello@there.com', 'bob@bobek.cz'], $to);
                \PHPUnit_Framework_Assert::assertEquals([], $cc);
                \PHPUnit_Framework_Assert::assertEquals(['hidden@translation.in'], $bcc);
            }));

        $sendEmailToUser = new SendEmailToUser($mockEmailModel);

        $config = [
            'useremail' => [
                'email' => 33,
            ],
            'user_id'  => [6, 7],
            'to_owner' => true,
            'to'       => 'hello@there.com, bob@bobek.cz',
            'bcc'      => 'hidden@translation.in',
        ];

        $sendEmailToUser->sendEmailToUsers($config, $lead);
    }
}
