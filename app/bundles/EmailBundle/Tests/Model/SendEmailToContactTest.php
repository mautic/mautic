<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\EmailBundle\Exception\FailedToSendToContactException;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\EmailBundle\Model\SendEmailToContact;
use Mautic\EmailBundle\Swiftmailer\Exception\BatchQueueMaxException;
use Mautic\EmailBundle\Tests\Helper\Transport\BatchTransport;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\DoNotContact;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Routing\Router;

class SendEmailToContactTest extends \PHPUnit_Framework_TestCase
{
    protected $contacts = [
        [
            'id'        => 1,
            'email'     => 'contact1@somewhere.com',
            'firstname' => 'Contact',
            'lastname'  => '1',
            'owner_id'  => 1,
        ],
        [
            'id'        => 2,
            'email'     => 'contact2@somewhere.com',
            'firstname' => 'Contact',
            'lastname'  => '2',
            'owner_id'  => 0,
        ],
        [
            'id'        => 3,
            'email'     => 'contact3@somewhere.com',
            'firstname' => 'Contact',
            'lastname'  => '3',
            'owner_id'  => 2,
        ],
        [
            'id'        => 4,
            'email'     => 'contact4@somewhere.com',
            'firstname' => 'Contact',
            'lastname'  => '4',
            'owner_id'  => 1,
        ],
    ];

    /**
     * @testdox Tests that all contacts are temporarily failed if an Email entity happens to be incorrectly configured
     *
     * @covers \Mautic\EmailBundle\Model\SendEmailToContact::setEmail()
     * @covers \Mautic\EmailBundle\Model\SendEmailToContact::setContact()
     * @covers \Mautic\EmailBundle\Model\SendEmailToContact::send()
     * @covers \Mautic\EmailBundle\Model\SendEmailToContact::finalFlush()
     * @covers \Mautic\EmailBundle\Model\SendEmailToContact::failContact()
     * @covers \Mautic\EmailBundle\Model\SendEmailToContact::getFailedContacts()
     */
    public function testContactsAreFailedIfSettingEmailEntityFails()
    {
        $mailHelper = $this->getMockBuilder(MailHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mailHelper->method('setEmail')
            ->willReturn(false);

        $statRepository = $this->getMockBuilder(StatRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dncModel = $this->getMockBuilder(DoNotContact::class)
            ->disableOriginalConstructor()
            ->getMock();

        // This should not be called because contact emails are just fine; the problem is with the email entity
        $dncModel->expects($this->never())
            ->method('addDncForContact');

        $translator = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $model = new SendEmailToContact($mailHelper, $statRepository, $dncModel, $translator);

        $email = new Email();
        $model->setEmail($email);

        foreach ($this->contacts as $contact) {
            try {
                $model->setContact($contact)
                    ->send();
            } catch (FailedToSendToContactException $exception) {
            }
        }

        $model->finalFlush();

        $failedContacts = $model->getFailedContacts();

        $this->assertCount(4, $failedContacts);
    }

    /**
     * @testdox Tests that bad emails are failed
     *
     * @covers \Mautic\EmailBundle\Model\SendEmailToContact::setContact()
     * @covers \Mautic\EmailBundle\Model\SendEmailToContact::send()
     * @covers \Mautic\EmailBundle\Model\SendEmailToContact::finalFlush()
     * @covers \Mautic\EmailBundle\Model\SendEmailToContact::failContact()
     * @covers \Mautic\EmailBundle\Model\SendEmailToContact::getFailedContacts()
     */
    public function testExceptionIsThrownIfEmailIsSentToBadContact()
    {
        $emailMock = $this->getMockBuilder(Email::class)
            ->getMock();
        $emailMock
            ->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1));

        $mailHelper = $this->getMockBuilder(MailHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mailHelper->method('setEmail')
            ->willReturn(true);
        $mailHelper->method('addTo')
            ->willReturnCallback(
                function ($email) {
                    return '@bad.com' !== $email;
                }
            );
        $mailHelper->method('queue')
            ->willReturn([true, []]);

        $stat = new Stat();
        $stat->setEmail($emailMock);
        $mailHelper->method('createEmailStat')
            ->willReturn($stat);

        $statRepository = $this->getMockBuilder(StatRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dncModel = $this->getMockBuilder(DoNotContact::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dncModel->expects($this->once())
            ->method('addDncForContact');

        $translator = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $model = new SendEmailToContact($mailHelper, $statRepository, $dncModel, $translator);
        $model->setEmail($emailMock);

        $contacts             = $this->contacts;
        $contacts[0]['email'] = '@bad.com';

        $exceptionThrown = false;
        foreach ($contacts as $contact) {
            try {
                $model->setContact($contact)
                    ->send();
            } catch (FailedToSendToContactException $exception) {
                $exceptionThrown = true;
            }
        }

        if (!$exceptionThrown) {
            $this->fail('FailedToSendToContactException not thrown');
        }

        $model->finalFlush();

        $failedContacts = $model->getFailedContacts();

        $this->assertCount(1, $failedContacts);
    }

    /**
     * @testdox Test a tokenized transport that limits batches does not throw BatchQueueMaxException on subsequent contacts when one fails
     *
     * @covers \Mautic\EmailBundle\Model\SendEmailToContact::setContact()
     * @covers \Mautic\EmailBundle\Model\SendEmailToContact::send()
     * @covers \Mautic\EmailBundle\Model\SendEmailToContact::failContact()
     * @covers \Mautic\EmailBundle\Model\SendEmailToContact::getFailedContacts()
     */
    public function testBadEmailDoesNotCauseBatchQueueMaxExceptionOnSubsequentContacts()
    {
        defined('MAUTIC_ENV') or define('MAUTIC_ENV', 'test');

        $emailMock = $this->getMockBuilder(Email::class)
            ->getMock();
        $emailMock
            ->method('getId')
            ->will($this->returnValue(1));
        $emailMock->method('getFromAddress')
            ->willReturn('test@mautic.com');

        // Use our test token transport limiting to 1 recipient per queue
        $transport = new BatchTransport(false, 1);
        $mailer    = new \Swift_Mailer($transport);

        // Mock factory to ensure that queue mode is handled until MailHelper is refactored completely away from MauticFactory
        $factoryMock = $this->getMockBuilder(MauticFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $factoryMock->method('getParameter')
            ->willReturnCallback(
                function ($param) {
                    switch ($param) {
                        case 'mailer_spool_type':
                            return 'memory';
                        default:
                            return '';
                    }
                }
            );
        $factoryMock->method('getLogger')
            ->willReturn(
                new NullLogger()
            );
        $factoryMock->method('getDispatcher')
            ->willReturn(
                new EventDispatcher()
            );
        $routerMock = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->getMock();
        $factoryMock->method('getRouter')
            ->willReturn($routerMock);

        $mailHelper = $this->getMockBuilder(MailHelper::class)
            ->setConstructorArgs([$factoryMock, $mailer])
            ->setMethods(['createEmailStat'])
            ->getMock();

        $mailHelper->method('createEmailStat')
            ->willReturnCallback(
                function () use ($emailMock) {
                    $stat = new Stat();
                    $stat->setEmail($emailMock);

                    $leadMock = $this->getMockBuilder(Lead::class)
                        ->getMock();
                    $leadMock->method('getId')
                        ->willReturn(1);

                    $stat->setLead($leadMock);

                    return $stat;
                }
            );

        // Enable queueing
        $mailHelper->enableQueue();

        $statRepository = $this->getMockBuilder(StatRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dncModel = $this->getMockBuilder(DoNotContact::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dncModel->expects($this->exactly(1))
            ->method('addDncForContact');

        $translator = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $model = new SendEmailToContact($mailHelper, $statRepository, $dncModel, $translator);
        $model->setEmail($emailMock);

        $contacts             = $this->contacts;
        $contacts[0]['email'] = '@bad.com';

        foreach ($contacts as $contact) {
            try {
                $model->setContact($contact)
                    ->send();
            } catch (FailedToSendToContactException $exception) {
                // We're good here
            } catch (BatchQueueMaxException $exception) {
                $this->fail('BatchQueueMaxException thrown');
            }
        }

        $model->finalFlush();

        $failedContacts = $model->getFailedContacts();

        $this->assertCount(1, $failedContacts);

        // Our fake transport should have processed 3 metadatas
        $this->assertCount(3, $transport->getMetadatas());

        // We made it this far so all of the emails were processed despite a bad email in the batch
    }

    /**
     * @testdox Test that stat entries are saved in batches of 20
     *
     * @covers \Mautic\EmailBundle\Model\SendEmailToContact::setContact()
     * @covers \Mautic\EmailBundle\Model\SendEmailToContact::send()
     * @covers \Mautic\EmailBundle\Model\SendEmailToContact::failContact()
     * @covers \Mautic\EmailBundle\Model\SendEmailToContact::createContactStatEntry()
     * @covers \Mautic\EmailBundle\Model\SendEmailToContact::getFailedContacts()
     */
    public function testThatStatEntriesAreCreatedAndPersistedEveryBatch()
    {
        defined('MAUTIC_ENV') or define('MAUTIC_ENV', 'test');

        $emailMock = $this->getMockBuilder(Email::class)
            ->getMock();
        $emailMock
            ->method('getId')
            ->will($this->returnValue(1));
        $emailMock->method('getFromAddress')
            ->willReturn('test@mautic.com');

        // Use our test token transport limiting to 1 recipient per queue
        $transport = new BatchTransport(false, 1);
        $mailer    = new \Swift_Mailer($transport);

        // Mock factory to ensure that queue mode is handled until MailHelper is refactored completely away from MauticFactory
        $factoryMock = $this->getMockBuilder(MauticFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $factoryMock->method('getParameter')
            ->willReturnCallback(
                function ($param) {
                    switch ($param) {
                        case 'mailer_spool_type':
                            return 'memory';
                        default:
                            return '';
                    }
                }
            );
        $factoryMock->method('getLogger')
            ->willReturn(
                new NullLogger()
            );
        $factoryMock->method('getDispatcher')
            ->willReturn(
                new EventDispatcher()
            );
        $routerMock = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->getMock();
        $factoryMock->method('getRouter')
            ->willReturn($routerMock);

        $mailHelper = $this->getMockBuilder(MailHelper::class)
            ->setConstructorArgs([$factoryMock, $mailer])
            ->setMethods(['createEmailStat'])
            ->getMock();

        $mailHelper->expects($this->exactly(21))
            ->method('createEmailStat')
            ->willReturnCallback(
                function () use ($emailMock) {
                    $stat = new Stat();
                    $stat->setEmail($emailMock);

                    $leadMock = $this->getMockBuilder(Lead::class)
                        ->getMock();
                    $leadMock->method('getId')
                        ->willReturn(1);

                    $stat->setLead($leadMock);

                    return $stat;
                }
            );

        // Enable queueing
        $mailHelper->enableQueue();

        // Here's the test; this should be called after 20 contacts are processed
        $statRepository = $this->getMockBuilder(StatRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $statRepository->expects($this->exactly(2))
            ->method('saveEntities');

        $dncModel = $this->getMockBuilder(DoNotContact::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dncModel->expects($this->never())
            ->method('addDncForContact');

        $translator = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $model = new SendEmailToContact($mailHelper, $statRepository, $dncModel, $translator);
        $model->setEmail($emailMock);

        // Let's generate 20 bogus contacts
        $contacts = [];
        $counter  = 0;
        while ($counter <= 20) {
            $contacts[] = [
                'id'        => $counter,
                'email'     => 'email'.uniqid().'@somewhere.com',
                'firstname' => 'Contact',
                'lastname'  => uniqid(),
            ];

            ++$counter;
        }

        foreach ($contacts as $contact) {
            try {
                $model->setContact($contact)
                    ->send();
            } catch (FailedToSendToContactException $exception) {
                $this->fail('FailedToSendToContactException thrown: '.$exception->getMessage());
            } catch (BatchQueueMaxException $exception) {
                $this->fail('BatchQueueMaxException thrown: '.$exception->getMessage());
            }
        }

        $model->finalFlush();

        $failedContacts = $model->getFailedContacts();
        $this->assertCount(0, $failedContacts);
        $this->assertCount(21, $transport->getMetadatas());
    }

    /**
     * @testdox Test that a failed email from the transport is handled
     *
     * @covers \Mautic\EmailBundle\Model\SendEmailToContact::setContact()
     * @covers \Mautic\EmailBundle\Model\SendEmailToContact::send()
     * @covers \Mautic\EmailBundle\Model\SendEmailToContact::failContact()
     * @covers \Mautic\EmailBundle\Model\SendEmailToContact::getFailedContacts()
     * @covers \Mautic\EmailBundle\Model\SendEmailToContact::upEmailSentCount()
     * @covers \Mautic\EmailBundle\Model\SendEmailToContact::downEmailSentCount()
     * @covers \Mautic\EmailBundle\Model\SendEmailToContact::getSentCounts()
     */
    public function testThatAFailureFromTransportIsHandled()
    {
        defined('MAUTIC_ENV') or define('MAUTIC_ENV', 'test');

        $emailMock = $this->getMockBuilder(Email::class)
            ->getMock();
        $emailMock
            ->method('getId')
            ->will($this->returnValue(1));
        $emailMock->method('getFromAddress')
            ->willReturn('test@mautic.com');

        // Use our test token transport limiting to 1 recipient per queue
        $transport = new BatchTransport(true, 1);
        $mailer    = new \Swift_Mailer($transport);

        // Mock factory to ensure that queue mode is handled until MailHelper is refactored completely away from MauticFactory
        $factoryMock = $this->getMockBuilder(MauticFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $factoryMock->method('getParameter')
            ->willReturnCallback(
                function ($param) {
                    switch ($param) {
                        case 'mailer_spool_type':
                            return 'memory';
                        default:
                            return '';
                    }
                }
            );
        $factoryMock->method('getLogger')
            ->willReturn(
                new NullLogger()
            );
        $factoryMock->method('getDispatcher')
            ->willReturn(
                new EventDispatcher()
            );
        $routerMock = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->getMock();
        $factoryMock->method('getRouter')
            ->willReturn($routerMock);

        $mailHelper = $this->getMockBuilder(MailHelper::class)
            ->setConstructorArgs([$factoryMock, $mailer])
            ->setMethods(['createEmailStat'])
            ->getMock();

        $mailHelper->method('createEmailStat')
            ->willReturnCallback(
                function () use ($emailMock) {
                    $stat = new Stat();
                    $stat->setEmail($emailMock);

                    $leadMock = $this->getMockBuilder(Lead::class)
                        ->getMock();
                    $leadMock->method('getId')
                        ->willReturn(1);

                    $stat->setLead($leadMock);

                    return $stat;
                }
            );

        // Enable queueing
        $mailHelper->enableQueue();

        $statRepository = $this->getMockBuilder(StatRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dncModel = $this->getMockBuilder(DoNotContact::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dncModel->expects($this->never())
            ->method('addDncForContact');

        $translator = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $model = new SendEmailToContact($mailHelper, $statRepository, $dncModel, $translator);
        $model->setEmail($emailMock);

        foreach ($this->contacts as $contact) {
            try {
                $model->setContact($contact)
                    ->send();
            } catch (FailedToSendToContactException $exception) {
                // We're good here
            } catch (BatchQueueMaxException $exception) {
                $this->fail('BatchQueueMaxException thrown');
            }
        }

        $model->finalFlush();

        $failedContacts = $model->getFailedContacts();

        $this->assertCount(1, $failedContacts);

        $counts = $model->getSentCounts();

        // Should have increased to 4, one failed via the transport so back down to 3
        $this->assertEquals(3, $counts[1]);

        // One error message from the transport
        $errorMessages = $model->getErrors();
        $this->assertCount(1, $errorMessages);
    }
}
