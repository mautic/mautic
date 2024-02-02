<?php

namespace Mautic\EmailBundle\Tests\Model;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\AssetBundle\Model\AssetModel;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Helper\ThemeHelper;
use Mautic\CoreBundle\Twig\Helper\SlotsHelper;
use Mautic\EmailBundle\Entity\CopyRepository;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\EmailBundle\Exception\FailedToSendToContactException;
use Mautic\EmailBundle\Helper\DTO\AddressDTO;
use Mautic\EmailBundle\Helper\FromEmailHelper;
use Mautic\EmailBundle\Helper\MailHashHelper;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\EmailBundle\Model\SendEmailToContact;
use Mautic\EmailBundle\MonitoredEmail\Mailbox;
use Mautic\EmailBundle\Stat\StatHelper;
use Mautic\EmailBundle\Tests\Helper\Transport\BatchTransport;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\DoNotContact;
use Mautic\PageBundle\Model\RedirectModel;
use Mautic\PageBundle\Model\TrackableModel;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Routing\Router;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class SendEmailToContactTest extends \PHPUnit\Framework\TestCase
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

    /** @var MockObject&FromEmailHelper */
    private $fromEmailHelperMock;

    /** @var MockObject&CoreParametersHelper */
    private $coreParametersHelperMock;

    /** @var MockObject&Mailbox */
    private $mailboxMock;

    /** @var MockObject&LoggerInterface */
    private MockObject $loggerMock;

    private MailHashHelper $mailHashHelper;

    /** @var MockObject&AssetModel */
    private MockObject $assetModelMock;

    /** @var MockObject&EmailModel */
    private MockObject $emailModelMock;

    /** @var MockObject&TrackableModel */
    private MockObject $trackableModelMock;

    /** @var MockObject&RedirectModel */
    private MockObject $redirectModelMock;

    /** @var MockObject&Environment */
    private MockObject $twigMock;

    /** @var MockObject&PathsHelper */
    private MockObject $pathsHelperMock;

    /** @var MockObject&ThemeHelper */
    private MockObject $themeHelperMock;

    /** @var MockObject&Router */
    private MockObject $routerMock;

    /** @var MockObject&RequestStack */
    private MockObject $requestStackMock;

    /** @var MockObject&EventDispatcherInterface */
    private MockObject $eventDispatcherMock;

    /** @var MockObject&EntityManagerInterface */
    private MockObject $entityManagerMock;

    private SlotsHelper $slotsHelper;

    /** @var MockObject&TranslatorInterface */
    private MockObject $translatorMock;

    /** @var MockObject&MailHelper */
    private MockObject $mailHelperMock;

    /** @var MockObject&StatRepository */
    private MockObject $statRepositoryMock;

    /** @var MockObject&DoNotContact */
    private MockObject $dncModelMock;

    /** @var MockObject&Email */
    private MockObject $emailMock;

    private StatHelper $statHelper;

    /** @var MockObject&CopyRepository */
    private MockObject $copyRepositoryMock;

    protected function setUp(): void
    {
        $this->fromEmailHelperMock      = $this->createMock(FromEmailHelper::class);
        $this->coreParametersHelperMock = $this->createMock(CoreParametersHelper::class);
        $this->mailboxMock              = $this->createMock(Mailbox::class);
        $this->loggerMock               = $this->createMock(LoggerInterface::class);
        $this->mailHashHelper           = new MailHashHelper($this->coreParametersHelperMock);
        $this->translatorMock           = $this->createMock(TranslatorInterface::class);
        $this->assetModelMock           = $this->createMock(AssetModel::class);
        $this->emailModelMock           = $this->createMock(EmailModel::class);
        $this->trackableModelMock       = $this->createMock(TrackableModel::class);
        $this->redirectModelMock        = $this->createMock(RedirectModel::class);
        $this->twigMock                 = $this->createMock(Environment::class);
        $this->pathsHelperMock          = $this->createMock(PathsHelper::class);
        $this->themeHelperMock          = $this->createMock(ThemeHelper::class);
        $this->routerMock               = $this->createMock(Router::class);
        $this->requestStackMock         = $this->createMock(RequestStack::class);
        $this->eventDispatcherMock      = $this->createMock(EventDispatcherInterface::class);
        $this->entityManagerMock        = $this->createMock(EntityManagerInterface::class);
        $this->slotsHelper              = new SlotsHelper();
        $this->mailHelperMock           = $this->createMock(MailHelper::class);
        $this->statRepositoryMock       = $this->createMock(StatRepository::class);
        $this->dncModelMock             = $this->createMock(DoNotContact::class);
        $this->emailMock                = $this->createMock(Email::class);
        $this->statHelper               = new StatHelper($this->statRepositoryMock);
        $this->copyRepositoryMock       = $this->createMock(CopyRepository::class);
    }

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
    public function testContactsAreFailedIfSettingEmailEntityFails(): void
    {
        $this->mailHelperMock->method('setEmail')
            ->willReturn(false);

        // This should not be called because contact emails are just fine; the problem is with the email entity
        $this->dncModelMock->expects($this->never())
            ->method('addDncForContact');

        $statHelper = new StatHelper($this->statRepositoryMock);

        $model = new SendEmailToContact($this->mailHelperMock, $statHelper, $this->dncModelMock, $this->translatorMock);

        $email = new Email();
        $model->setEmail($email);

        foreach ($this->contacts as $contact) {
            try {
                $model->setContact($contact)
                    ->send();
            } catch (FailedToSendToContactException) {
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
    public function testExceptionIsThrownIfEmailIsSentToBadContact(): void
    {
        $this->emailMock
            ->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1));

        $this->mailHelperMock->method('setEmail')
            ->willReturn(true);
        $this->mailHelperMock->method('addTo')
            ->willReturnCallback(
                fn ($email) => '@bad.com' !== $email
            );
        $this->mailHelperMock->method('queue')
            ->willReturn([true, []]);

        $stat = new Stat();
        $stat->setEmail($this->emailMock);
        $this->mailHelperMock->method('createEmailStat')
            ->willReturn($stat);

        $this->dncModelMock->expects($this->once())
            ->method('addDncForContact');

        $model = new SendEmailToContact($this->mailHelperMock, $this->statHelper, $this->dncModelMock, $this->translatorMock);
        $model->setEmail($this->emailMock);

        $this->contacts[0]['email'] = '@bad.com';

        $exceptionThrown = false;
        foreach ($this->contacts as $contact) {
            try {
                $model->setContact($contact)
                    ->send();
            } catch (FailedToSendToContactException) {
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
    public function testBadEmailDoesNotCauseBatchQueueMaxExceptionOnSubsequentContacts(): void
    {
        $this->emailMock->method('getId')->will($this->returnValue(1));
        $this->emailMock->method('getFromAddress')->willReturn('test@mautic.com');
        $this->emailMock->method('getSubject')->willReturn('Subject');
        $this->emailMock->method('getCustomHtml')->willReturn('content');

        // Use our test token transport limiting to 1 recipient per queue
        $transport = new BatchTransport(false, 1);
        $mailer    = new Mailer($transport);

        $this->fromEmailHelperMock->method('getFromAddressConsideringOwner')
            ->willReturn(new AddressDTO('someone@somewhere.com'));

        $this->coreParametersHelperMock->method('get')->will($this->returnValueMap([['mailer_from_email', null, 'nobody@nowhere.com'], ['secret_key', null, 'secret']]));

        $this->mailHelperMock = $this->getMockBuilder(MailHelper::class)
            ->setConstructorArgs([
                $mailer,
                $this->fromEmailHelperMock,
                $this->coreParametersHelperMock,
                $this->mailboxMock,
                $this->loggerMock,
                $this->mailHashHelper,
                $this->assetModelMock,
                $this->emailModelMock,
                $this->trackableModelMock,
                $this->redirectModelMock,
                $this->twigMock,
                $this->pathsHelperMock,
                $this->themeHelperMock,
                $this->routerMock,
                $this->requestStackMock,
                $this->eventDispatcherMock,
                $this->entityManagerMock,
                $this->slotsHelper,
            ])
            ->onlyMethods(['createEmailStat'])
            ->getMock();

        $this->mailHelperMock->method('createEmailStat')
            ->will($this->returnCallback(
                function () {
                    $stat = new Stat();
                    $stat->setEmail($this->emailMock);
                    $leadMock = $this->getMockBuilder(Lead::class)
                       ->getMock();
                    $leadMock->method('getId')
                       ->willReturn(1);

                    $stat->setLead($leadMock);

                    return $stat;
                }
            ));

        // Enable queueing
        $this->mailHelperMock->enableQueue();

        $this->dncModelMock->expects($this->exactly(1))
            ->method('addDncForContact');

        $model = new SendEmailToContact($this->mailHelperMock, $this->statHelper, $this->dncModelMock, $this->translatorMock);
        $model->setEmail($this->emailMock);

        $this->contacts[0]['email'] = '@bad.com';

        foreach ($this->contacts as $contact) {
            try {
                $model->setContact($contact)
                    ->send();
            } catch (FailedToSendToContactException) {
                // We're good here
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
     * @testdox Test a tokenized transport that fills tokens correctly
     *
     * @covers \Mautic\EmailBundle\Model\SendEmailToContact::setContact()
     * @covers \Mautic\EmailBundle\Model\SendEmailToContact::send()
     * @covers \Mautic\EmailBundle\Model\SendEmailToContact::failContact()
     * @covers \Mautic\EmailBundle\Model\SendEmailToContact::getFailedContacts()
     */
    public function testBatchQueueContactsHaveTokensHydrated(): void
    {
        $this->coreParametersHelperMock->method('get')->will($this->returnValueMap([['mailer_from_email', null, 'nobody@nowhere.com'], ['secret_key', null, 'secret']]));

        $this->emailMock->method('getId')->will($this->returnValue(1));
        $this->emailMock->method('getFromAddress')->willReturn('test@mautic.com');
        $this->emailMock->method('getSubject')->willReturn('Subject');
        $this->emailMock->method('getCustomHtml')->willReturn('Hi {contactfield=firstname}');

        // Use our test token transport limiting to 1 recipient per queue
        $transport = new BatchTransport(false, 1);
        $mailer    = new Mailer($transport);

        $this->eventDispatcherMock->method('dispatch')
            ->willReturnCallback(
                function (EmailSendEvent $event, $eventName) {
                    $lead = $event->getLead();

                    $tokens = [];
                    foreach ($lead as $field => $value) {
                        $tokens["{contactfield=$field}"] = $value;
                    }
                    $tokens['{hash}'] = $event->getIdHash();

                    $event->addTokens($tokens);

                    return $event;
                }
            );

        $this->emailModelMock->method('getCopyRepository')
            ->willReturn($this->copyRepositoryMock);

        $this->fromEmailHelperMock->method('getFromAddressConsideringOwner')
            ->willReturn(new AddressDTO('someone@somewhere.com'));

        $this->mailHelperMock = $this->getMockBuilder(MailHelper::class)
            ->setConstructorArgs([
                $mailer,
                $this->fromEmailHelperMock,
                $this->coreParametersHelperMock,
                $this->mailboxMock,
                $this->loggerMock,
                $this->mailHashHelper,
                $this->assetModelMock,
                $this->emailModelMock,
                $this->trackableModelMock,
                $this->redirectModelMock,
                $this->twigMock,
                $this->pathsHelperMock,
                $this->themeHelperMock,
                $this->routerMock,
                $this->requestStackMock,
                $this->eventDispatcherMock,
                $this->entityManagerMock,
                $this->slotsHelper,
           ])
           ->onlyMethods([])
           ->getMock();

        // Enable queueing
        $this->mailHelperMock->enableQueue();

        $this->statRepositoryMock->method('saveEntity')
            ->willReturnCallback(
                function (Stat $stat): void {
                    $tokens = $stat->getTokens();
                    $this->assertGreaterThan(1, count($tokens));
                    $this->assertEquals($stat->getTrackingHash(), $tokens['{hash}']);
                }
            );

        $model = new SendEmailToContact($this->mailHelperMock, $this->statHelper, $this->dncModelMock, $this->translatorMock);
        $model->setEmail($this->emailMock);

        foreach ($this->contacts as $contact) {
            try {
                $model->setContact($contact)
                    ->send();
            } catch (FailedToSendToContactException) {
                // We're good here
            }
        }

        $model->finalFlush();

        $this->assertCount(4, $transport->getMetadatas());
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
    public function testThatStatEntriesAreCreatedAndPersistedEveryBatch(): void
    {
        $this->coreParametersHelperMock->method('get')->will($this->returnValueMap([['mailer_from_email', null, 'nobody@nowhere.com'], ['secret_key', null, 'secret']]));

        $this->emailMock->method('getId')->willReturn(1);
        $this->emailMock->method('getFromAddress')->willReturn('test@mautic.com');
        $this->emailMock->method('getSubject')->willReturn('Subject');
        $this->emailMock->method('getCustomHtml')->willReturn('content');

        // Use our test token transport limiting to 1 recipient per queue
        $transport = new BatchTransport(false, 1);
        $mailer    = new Mailer($transport);

        $this->fromEmailHelperMock->method('getFromAddressConsideringOwner')
            ->willReturn(new AddressDTO('someone@somewhere.com'));

        $this->mailHelperMock = $this->getMockBuilder(MailHelper::class)
          ->setConstructorArgs([
            $mailer,
            $this->fromEmailHelperMock,
            $this->coreParametersHelperMock,
            $this->mailboxMock,
            $this->loggerMock,
            $this->mailHashHelper,
            $this->assetModelMock,
            $this->emailModelMock,
            $this->trackableModelMock,
            $this->redirectModelMock,
            $this->twigMock,
            $this->pathsHelperMock,
            $this->themeHelperMock,
            $this->routerMock,
            $this->requestStackMock,
            $this->eventDispatcherMock,
            $this->entityManagerMock,
            $this->slotsHelper,
          ])
            ->onlyMethods(['createEmailStat'])
            ->getMock();

        $this->mailHelperMock->method('createEmailStat')
            ->will($this->returnCallback(
                function () {
                    $stat = new Stat();
                    $stat->setEmail($this->emailMock);
                    $leadMock = $this->getMockBuilder(Lead::class)
                          ->getMock();
                    $leadMock->method('getId')
                      ->willReturn(1);

                    $stat->setLead($leadMock);

                    return $stat;
                }
            ));

        // Enable queueing
        $this->mailHelperMock->enableQueue();

        // Here's the test; this should be called after 20 contacts are processed
        $this->statRepositoryMock->expects($this->exactly(21))
            ->method('saveEntity');

        $this->dncModelMock->expects($this->never())
            ->method('addDncForContact');

        $model = new SendEmailToContact($this->mailHelperMock, $this->statHelper, $this->dncModelMock, $this->translatorMock);
        $model->setEmail($this->emailMock);

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
    public function testThatAFailureFromTransportIsHandled(): void
    {
        $this->coreParametersHelperMock->method('get')->will($this->returnValueMap([['mailer_from_email', null, 'nobody@nowhere.com'], ['secret_key', null, 'secret']]));

        $this->emailMock->method('getId')->willReturn(1);
        $this->emailMock->method('getFromAddress')->willReturn('test@mautic.com');
        $this->emailMock->method('getSubject')->willReturn(''); // The subject must be empty for the email to fail.
        $this->emailMock->method('getCustomHtml')->willReturn('content');

        // Use our test token transport limiting to 1 recipient per queue
        $transport = new BatchTransport(true, 1);
        $mailer    = new Mailer($transport);

        $this->fromEmailHelperMock->method('getFromAddressConsideringOwner')->willReturn(new AddressDTO('someone@somewhere.com'));

        $this->mailHelperMock = $this->getMockBuilder(MailHelper::class)
            ->setConstructorArgs([
                  $mailer,
                  $this->fromEmailHelperMock,
                  $this->coreParametersHelperMock,
                  $this->mailboxMock,
                  $this->loggerMock,
                  $this->mailHashHelper,
                  $this->assetModelMock,
                  $this->emailModelMock,
                  $this->trackableModelMock,
                  $this->redirectModelMock,
                  $this->twigMock,
                  $this->pathsHelperMock,
                  $this->themeHelperMock,
                  $this->routerMock,
                  $this->requestStackMock,
                  $this->eventDispatcherMock,
                  $this->entityManagerMock,
                  $this->slotsHelper,
            ])
            ->onlyMethods(['createEmailStat'])
            ->getMock();

        $this->mailHelperMock->method('createEmailStat')
            ->will($this->returnCallback(
                function () {
                    $stat = new Stat();
                    $stat->setEmail($this->emailMock);
                    $leadMock = $this->getMockBuilder(Lead::class)
                        ->getMock();
                    $leadMock->method('getId')
                        ->willReturn(1);

                    $stat->setLead($leadMock);

                    return $stat;
                }
            ));

        // Enable queueing
        $this->mailHelperMock->enableQueue();

        $this->dncModelMock->expects($this->never())->method('addDncForContact');

        $model = new SendEmailToContact($this->mailHelperMock, $this->statHelper, $this->dncModelMock, $this->translatorMock);
        $model->setEmail($this->emailMock);

        foreach ($this->contacts as $contact) {
            try {
                $model->setContact($contact)->send();
            } catch (FailedToSendToContactException) {
                // We're good here
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

    /**
     * @testdox Test that sending an email with invalid Bcc address is handled
     *
     * @covers \Mautic\EmailBundle\Model\SendEmailToContact::setContact()
     * @covers \Mautic\EmailBundle\Model\SendEmailToContact::send()
     * @covers \Mautic\EmailBundle\Model\SendEmailToContact::failContact()
     */
    public function testThatInvalidBccFailureIsHandled(): void
    {
        defined('MAUTIC_ENV') or define('MAUTIC_ENV', 'test');

        $this->coreParametersHelperMock->method('get')
            ->willReturnMap(
                [
                    ['mailer_from_email', null, 'nobody@nowhere.com'],
                    ['mailer_from_name', null, 'No Body'],
                ]
            );

        $mailer         = new Mailer(new BatchTransport());

        $this->mailHelperMock = $this->getMockBuilder(MailHelper::class)
            ->setConstructorArgs([
                $mailer,
                $this->fromEmailHelperMock,
                $this->coreParametersHelperMock,
                $this->mailboxMock,
                $this->loggerMock,
                $this->mailHashHelper,
                $this->assetModelMock,
                $this->emailModelMock,
                $this->trackableModelMock,
                $this->redirectModelMock,
                $this->twigMock,
                $this->pathsHelperMock,
                $this->themeHelperMock,
                $this->routerMock,
                $this->requestStackMock,
                $this->eventDispatcherMock,
                $this->entityManagerMock,
                $this->slotsHelper,
            ])
        ->onlyMethods([])
        ->getMock();

        $model          = new SendEmailToContact($this->mailHelperMock, $this->statHelper, $this->dncModelMock, $this->translatorMock);
        $this->emailMock->method('getId')->willReturn(1);
        $this->emailMock->method('getSubject')->willReturn('subject');
        $this->emailMock->method('getCustomHtml')->willReturn('content');

        // Set invalid BCC (should use comma as separator)
        $this->emailMock
            ->expects($this->any())
            ->method('getBccAddress')
            ->willReturn('test@mautic.com; test@mautic.com');

        $model->setEmail($this->emailMock);

        $stat = new Stat();
        $stat->setEmail($this->emailMock);

        $this->expectException(FailedToSendToContactException::class);
        $this->expectExceptionMessage('Email "test@mautic.com; test@mautic.com" does not comply with addr-spec of RFC 2822.');

        // Send should trigger the FailedToSendToContactException
        $model->setContact($this->contacts[0])->send();
    }
}
