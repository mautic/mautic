<?php

namespace Mautic\EmailBundle\Tests\Model;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Mautic\AssetBundle\Model\AssetModel;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Helper\ThemeHelper;
use Mautic\EmailBundle\Entity\CopyRepository;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\EmailBundle\Exception\FailedToSendToContactException;
use Mautic\EmailBundle\Helper\DTO\AddressDTO;
use Mautic\EmailBundle\Helper\FromEmailHelper;
use Mautic\EmailBundle\Helper\MailHashHelper;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\EmailBundle\Model\EmailStatModel;
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
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class SendEmailToContactTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var array<array<string,int|string>>
     */
    private array $contacts = [
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
     * @var MockObject&FromEmailHelper
     */
    private $fromEmaiHelper;

    /**
     * @var MockObject&CoreParametersHelper
     */
    private $coreParametersHelper;

    /**
     * @var MockObject&Mailbox
     */
    private $mailbox;

    /**
     * @var MockObject&LoggerInterface
     */
    private MockObject $loggerMock;

    private MailHashHelper $mailHashHelper;

    /**
     * @var MockObject&TranslatorInterface
     */
    private MockObject $translator;

    /**
     * @var MockObject|MailHelper
     */
    private $mailHelper;

    /**
     * @var MockObject|DoNotContact
     */
    private $dncModel;

    /**
     * @var MockObject|EmailStatModel
     */
    private $emailStatModel;

    /**
     * @var MockObject&Environment
     */
    private MockObject $twig;

    private StatHelper $statHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dncModel             = $this->createMock(DoNotContact::class);
        $this->mailHelper           = $this->createMock(MailHelper::class);
        $this->emailStatModel       = $this->createMock(EmailStatModel::class);
        $this->statHelper           = new StatHelper($this->emailStatModel);
        $this->fromEmaiHelper       = $this->createMock(FromEmailHelper::class);
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->mailbox              = $this->createMock(Mailbox::class);
        $this->loggerMock           = $this->createMock(LoggerInterface::class);
        $this->mailHashHelper       = new MailHashHelper($this->coreParametersHelper);
        $this->translator           = $this->createMock(TranslatorInterface::class);
        $this->twig                 = $this->createMock(Environment::class);

        $this->fromEmaiHelper->method('getFrom')
            ->willReturn(new AddressDTO('someone@somewhere.com'));
    }

    /**
     * @testdox Tests that all contacts are temporarily failed if an Email entity happens to be incorrectly configured
     */
    public function testContactsAreFailedIfSettingEmailEntityFails(): void
    {
        $this->mailHelper->method('setEmail')
            ->willReturn(false);

        // This should not be called because contact emails are just fine; the problem is with the email entity
        $this->dncModel->expects($this->never())
            ->method('addDncForContact');

        $model = new SendEmailToContact($this->mailHelper, $this->statHelper, $this->dncModel, $this->translator);

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
     */
    public function testExceptionIsThrownIfEmailIsSentToBadContact(): void
    {
        $emailMock = $this->getMockBuilder(Email::class)
            ->getMock();
        $emailMock
            ->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1));

        $this->mailHelper->method('setEmail')
            ->willReturn(true);
        $this->mailHelper->method('addTo')
            ->willReturnCallback(
                fn ($email) => '@bad.com' !== $email
            );
        $this->mailHelper->method('queue')
            ->willReturn([true, []]);

        $stat = new Stat();
        $stat->setEmail($emailMock);
        $this->mailHelper->method('createEmailStat')
            ->willReturn($stat);

        $this->dncModel->expects($this->once())
            ->method('addDncForContact');

        $model = new SendEmailToContact($this->mailHelper, $this->statHelper, $this->dncModel, $this->translator);
        $model->setEmail($emailMock);

        $contacts             = $this->contacts;
        $contacts[0]['email'] = '@bad.com';

        $exceptionThrown = false;
        foreach ($contacts as $contact) {
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
     */
    public function testBadEmailDoesNotCauseBatchQueueMaxExceptionOnSubsequentContacts(): void
    {
        $emailMock = $this->createMock(Email::class);
        $emailMock->method('getId')->will($this->returnValue(1));
        $emailMock->method('getFromAddress')->willReturn('test@mautic.com');
        $emailMock->method('getSubject')->willReturn('Subject');
        $emailMock->method('getCustomHtml')->willReturn('content');

        // Use our test token transport limiting to 1 recipient per queue
        $transport = new BatchTransport(false, 1);
        $mailer    = new Mailer($transport);

        $routerMock  = $this->createMock(Router::class);

        $requestStack = new RequestStack();

        $this->fromEmaiHelper->method('getFromAddressConsideringOwner')
            ->willReturn(new AddressDTO('someone@somewhere.com'));

        $this->coreParametersHelper->method('get')->willReturnCallback(
            fn ($param) => match ($param) {
                'mailer_from_email' => 'nobody@nowhere.com',
                'secret_key'        => 'secret',
                default             => '',
            }
        );

        $themeHelper = $this->createMock(ThemeHelper::class);
        $themeHelper->expects(self::never())
            ->method('checkForTwigTemplate');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never()) // Never to make sure that the mock is properly tested if needed.
            ->method('getReference');

        $mailHelper = $this->getMockBuilder(MailHelper::class)
            ->setConstructorArgs([
                $mailer,
                $this->fromEmaiHelper,
                $this->coreParametersHelper,
                $this->mailbox,
                $this->loggerMock,
                $this->mailHashHelper,
                $routerMock,
                $this->twig,
                $themeHelper,
                $this->createMock(PathsHelper::class),
                $this->createMock(EventDispatcherInterface::class),
                $requestStack,
                $entityManager,
                $this->createMock(ModelFactory::class),
                $this->createMock(AssetModel::class),
                $this->createMock(TrackableModel::class),
                $this->createMock(RedirectModel::class),
            ])
            ->onlyMethods(['createEmailStat'])
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

        $this->dncModel->expects($this->exactly(1))
            ->method('addDncForContact');

        $model = new SendEmailToContact($mailHelper, $this->statHelper, $this->dncModel, $this->translator);
        $model->setEmail($emailMock);

        $contacts             = $this->contacts;
        $contacts[0]['email'] = '@bad.com';

        foreach ($contacts as $contact) {
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
     */
    public function testBatchQueueContactsHaveTokensHydrated(): void
    {
        $this->coreParametersHelper->method('get')->will($this->returnValueMap([['mailer_from_email', null, 'nobody@nowhere.com'], ['secret_key', null, 'secret']]));

        $emailMock = $this->createMock(Email::class);
        $emailMock->method('getId')->will($this->returnValue(1));
        $emailMock->method('getFromAddress')->willReturn('test@mautic.com');
        $emailMock->method('getSubject')->willReturn('Subject');
        $emailMock->method('getCustomHtml')->willReturn('Hi {contactfield=firstname}');

        // Use our test token transport limiting to 1 recipient per queue
        $transport = new BatchTransport(false, 1);
        $mailer    = new Mailer($transport);

        // Mock factory to remove when factory is completely gone.
        $this->coreParametersHelper->method('get')
            ->willReturnCallback(
                fn ($param) => match ($param) {
                    default => '',
                }
            );

        $mockDispatcher = $this->getMockBuilder(EventDispatcher::class)
            ->getMock();
        $mockDispatcher->method('dispatch')
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

        $copyRepoMock   = $this->createMock(CopyRepository::class);
        $emailModelMock = $this->createMock(EmailModel::class);
        $emailModelMock->method('getCopyRepository')
            ->willReturn($copyRepoMock);

        $modelFactory = $this->createMock(ModelFactory::class);
        $modelFactory->method('getModel')
            ->with(EmailModel::class)
            ->willReturn($emailModelMock);

        $this->fromEmaiHelper->method('getFromAddressConsideringOwner')
            ->willReturn(new AddressDTO('someone@somewhere.com'));

        $themeHelper = $this->createMock(ThemeHelper::class);
        $themeHelper->expects(self::never())
            ->method('checkForTwigTemplate');

        $mailHelper = $this->getMockBuilder(MailHelper::class)
            ->setConstructorArgs([
                $mailer,
                $this->fromEmaiHelper,
                $this->coreParametersHelper,
                $this->mailbox,
                $this->loggerMock,
                $this->mailHashHelper,
                $this->createMock(Router::class),
                $this->twig,
                $themeHelper,
                $this->createMock(PathsHelper::class),
                $mockDispatcher,
                new RequestStack(),
                $this->createMock(EntityManager::class),
                $modelFactory,
                $this->createMock(AssetModel::class),
                $this->createMock(TrackableModel::class),
                $this->createMock(RedirectModel::class),
            ])
            ->onlyMethods([])
            ->getMock();

        // Enable queueing
        $mailHelper->enableQueue();

        $this->emailStatModel->method('saveEntity')
            ->willReturnCallback(
                function (Stat $stat): void {
                    $tokens = $stat->getTokens();
                    $this->assertGreaterThan(1, count($tokens));
                    $this->assertEquals($stat->getTrackingHash(), $tokens['{hash}']);
                }
            );

        $model = new SendEmailToContact($mailHelper, $this->statHelper, $this->dncModel, $this->translator);
        $model->setEmail($emailMock);

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
     */
    public function testThatStatEntriesAreCreatedAndPersistedEveryBatch(): void
    {
        $this->coreParametersHelper->method('get')->will($this->returnValueMap([['mailer_from_email', null, 'nobody@nowhere.com'], ['secret_key', null, 'secret']]));

        $emailMock = $this->createMock(Email::class);
        $emailMock->method('getId')->willReturn(1);
        $emailMock->method('getFromAddress')->willReturn('test@mautic.com');
        $emailMock->method('getSubject')->willReturn('Subject');
        $emailMock->method('getCustomHtml')->willReturn('content');

        // Use our test token transport limiting to 1 recipient per queue
        $transport = new BatchTransport(false, 1);
        $mailer    = new Mailer($transport);

        $this->coreParametersHelper->method('get')
            ->willReturnCallback(
                fn ($param) => match ($param) {
                    default => '',
                }
            );
        $routerMock = $this->createMock(Router::class);

        $this->fromEmaiHelper->method('getFromAddressConsideringOwner')
            ->willReturn(new AddressDTO('someone@somewhere.com'));

        $themeHelper = $this->createMock(ThemeHelper::class);
        $themeHelper->expects(self::never())
            ->method('checkForTwigTemplate');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never()) // Never to make sure that the mock is properly tested if needed.
            ->method('getReference');

        $requestStack = new RequestStack();

        $mailHelper = $this->getMockBuilder(MailHelper::class)
            ->setConstructorArgs([
                $mailer,
                $this->fromEmaiHelper,
                $this->coreParametersHelper,
                $this->mailbox,
                $this->loggerMock,
                $this->mailHashHelper,
                $routerMock,
                $this->twig,
                $themeHelper,
                $this->createMock(PathsHelper::class),
                $this->createMock(EventDispatcherInterface::class),
                $requestStack,
                $entityManager,
                $this->createMock(ModelFactory::class),
                $this->createMock(AssetModel::class),
                $this->createMock(TrackableModel::class),
                $this->createMock(RedirectModel::class),
            ])
            ->onlyMethods(['createEmailStat'])
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
        $this->emailStatModel->expects($this->exactly(21))
            ->method('saveEntity');

        $this->dncModel->expects($this->never())
            ->method('addDncForContact');

        $model = new SendEmailToContact($mailHelper, $this->statHelper, $this->dncModel, $this->translator);
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
            }
        }

        $model->finalFlush();

        $failedContacts = $model->getFailedContacts();
        $this->assertCount(0, $failedContacts);
        $this->assertCount(21, $transport->getMetadatas());
    }

    /**
     * @testdox Test that a failed email from the transport is handled
     */
    public function testThatAFailureFromTransportIsHandled(): void
    {
        $this->coreParametersHelper->method('get')->will($this->returnValueMap([['mailer_from_email', null, 'nobody@nowhere.com'], ['secret_key', null, 'secret']]));

        $emailMock = $this->createMock(Email::class);
        $emailMock->method('getId')->willReturn(1);
        $emailMock->method('getFromAddress')->willReturn('test@mautic.com');
        $emailMock->method('getSubject')->willReturn(''); // The subject must be empty for the email to fail.
        $emailMock->method('getCustomHtml')->willReturn('content');

        // Use our test token transport limiting to 1 recipient per queue
        $transport = new BatchTransport(true, 1);
        $mailer    = new Mailer($transport);

        $this->coreParametersHelper->method('get')
            ->willReturnCallback(
                fn ($param) => match ($param) {
                    default => '',
                }
            );

        $this->fromEmaiHelper->method('getFromAddressConsideringOwner')->willReturn(new AddressDTO('someone@somewhere.com'));
        $routerMock = $this->createMock(Router::class);
        $twig       = $this->createMock(Environment::class);

        $themeHelper = $this->createMock(ThemeHelper::class);
        $themeHelper->expects(self::never())
            ->method('checkForTwigTemplate');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never()) // Never to make sure that the mock is properly tested if needed.
            ->method('getReference');

        $requestStack = new RequestStack();

        /** @var MockObject&MailHelper $mailHelper */
        $mailHelper = $this->getMockBuilder(MailHelper::class)
            ->setConstructorArgs([
                $mailer,
                $this->fromEmaiHelper,
                $this->coreParametersHelper,
                $this->mailbox,
                $this->loggerMock,
                $this->mailHashHelper,
                $routerMock,
                $this->twig,
                $themeHelper,
                $this->createMock(PathsHelper::class),
                $this->createMock(EventDispatcherInterface::class),
                $requestStack,
                $entityManager,
                $this->createMock(ModelFactory::class),
                $this->createMock(AssetModel::class),
                $this->createMock(TrackableModel::class),
                $this->createMock(RedirectModel::class),
            ])
            ->onlyMethods(['createEmailStat'])
            ->getMock();

        $mailHelper->method('createEmailStat')
            ->willReturnCallback(
                function () use ($emailMock) {
                    $stat = new Stat();
                    $stat->setEmail($emailMock);

                    $leadMock = $this->createMock(Lead::class);
                    $leadMock->method('getId')->willReturn(1);

                    $stat->setLead($leadMock);

                    return $stat;
                }
            );

        // Enable queueing
        $mailHelper->enableQueue();

        $this->dncModel->expects($this->never())->method('addDncForContact');

        $model = new SendEmailToContact($mailHelper, $this->statHelper, $this->dncModel, $this->translator);
        $model->setEmail($emailMock);

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
     */
    public function testThatInvalidBccFailureIsHandled(): void
    {
        defined('MAUTIC_ENV') or define('MAUTIC_ENV', 'test');

        /** @var MockObject&FromEmailHelper $fromEmailHelper */
        $fromEmailHelper = $this->createMock(FromEmailHelper::class);

        /** @var MockObject&CoreParametersHelper $coreParametersHelper */
        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);

        /** @var MockObject&Mailbox $mailbox */
        $mailbox = $this->createMock(Mailbox::class);

        /** @var MockObject&LoggerInterface $logger */
        $logger = $this->createMock(LoggerInterface::class);

        /** @var MockObject&RouterInterface $router */
        $router = $this->createMock(RouterInterface::class);

        /** @var MockObject&Environment $twig */
        $twig = $this->createMock(Environment::class);

        $themeHelper = $this->createMock(ThemeHelper::class);
        $themeHelper->expects(self::never())
            ->method('checkForTwigTemplate');

        $coreParametersHelper->method('get')
            ->willReturnMap(
                [
                    ['mailer_from_email', null, 'nobody@nowhere.com'],
                    ['mailer_from_name', null, 'No Body'],
                    ['mailer_return_path', false, null],
                ]
            );

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never()) // Never to make sure that the mock is properly tested if needed.
            ->method('getReference');

        $requestStack = new RequestStack();

        $mailer         = new Mailer(new BatchTransport());
        $mailHelper     = new MailHelper(
            $mailer,
            $fromEmailHelper,
            $coreParametersHelper,
            $mailbox,
            $logger,
            $this->mailHashHelper,
            $router,
            $twig,
            $themeHelper,
            $this->createMock(PathsHelper::class),
            $this->createMock(EventDispatcherInterface::class),
            $requestStack,
            $entityManager,
            $this->createMock(ModelFactory::class),
            $this->createMock(AssetModel::class),
            $this->createMock(TrackableModel::class),
            $this->createMock(RedirectModel::class),
        );
        $dncModel       = $this->createMock(DoNotContact::class);
        $translator     = $this->createMock(TranslatorInterface::class);
        $model          = new SendEmailToContact($mailHelper, $this->statHelper, $dncModel, $translator);
        $emailMock      = $this->createMock(Email::class);
        $emailMock->method('getId')->willReturn(1);
        $emailMock->method('getSubject')->willReturn('subject');
        $emailMock->method('getCustomHtml')->willReturn('content');

        // Set invalid BCC (should use comma as separator)
        $emailMock
            ->expects($this->any())
            ->method('getBccAddress')
            ->willReturn('test@mautic.com; test@mautic.com');

        $model->setEmail($emailMock);

        $stat = new Stat();
        $stat->setEmail($emailMock);

        $this->expectException(FailedToSendToContactException::class);
        $this->expectExceptionMessage('Email "test@mautic.com; test@mautic.com" does not comply with addr-spec of RFC 2822.');

        // Send should trigger the FailedToSendToContactException
        $model->setContact($this->contacts[0])->send();
    }
}
