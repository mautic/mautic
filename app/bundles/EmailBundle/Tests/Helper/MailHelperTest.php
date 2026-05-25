<?php

namespace Mautic\EmailBundle\Tests\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\AssetBundle\Model\AssetModel;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Helper\ThemeHelper;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\EmailBundle\Exception\InvalidEmailException;
use Mautic\EmailBundle\Helper\FromEmailHelper;
use Mautic\EmailBundle\Helper\MailHashHelper;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\EmailBundle\Helper\SMimeHelper;
use Mautic\EmailBundle\Mailer\Exception\BatchQueueMaxException;
use Mautic\EmailBundle\Model\EmailStatModel;
use Mautic\EmailBundle\MonitoredEmail\Mailbox;
use Mautic\EmailBundle\Tests\Helper\Transport\BatchTransport;
use Mautic\EmailBundle\Tests\Helper\Transport\BcInterfaceTokenTransport;
use Mautic\EmailBundle\Tests\Helper\Transport\SmtpTransport;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PageBundle\Model\RedirectModel;
use Mautic\PageBundle\Model\TrackableModel;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Exception\LogicException;
use Symfony\Component\Mime\Header\HeaderInterface;
use Symfony\Component\Mime\Header\MailboxListHeader;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Twig\Environment;

class MailHelperTest extends TestCase
{
    private const MINIFY_HTML = '<!doctype html>
    <html lang=3D"en" xmlns=3D"http://www.w3.org/1999/xhtml" xmlns:v=3D"urn:schemas-microsoft-com:vml" xmlns:o=3D"urn:schemas-microsoft-com:office:office">
      <head>
        <title>Test</title>
        <body style=3D"word-spacing:normal;background-color:#FFFFFF;">
            <div  style=3D"background:#FFFFFF;background-color:#FFFFFF;margin:0pxauto;max-width:600px;">
        </body>
    </html>';

    /**
     * @var array<array<int|string|null>>
     */
    private array $defaultParams = [
        ['mailer_from_email', null, 'nobody@nowhere.com'],
        ['mailer_from_name', null, 'No Body'],
        ['mailer_address_length_limit', null, 320],
    ];

    private FromEmailHelper $fromEmailHelper;

    private CoreParametersHelper&MockObject $coreParametersHelper;

    private Mailbox&MockObject $mailbox;

    private LeadRepository&MockObject $contactRepository;

    private LoggerInterface&MockObject $logger;

    private RouterInterface&MockObject $router;

    private Environment&MockObject $twig;

    private ThemeHelper&MockObject $themeHelper;

    private EventDispatcherInterface&MockObject $dispatcher;

    private PathsHelper&MockObject $pathsHelper;

    private MailHashHelper $mailHashHelper;

    private RequestStack&MockObject $requestStack;

    private EntityManagerInterface&MockObject $entityManager;

    private SMimeHelper&MockObject $sMimeHelper;

    private MockObject&AssetModel $assetModel;

    private MockObject&TrackableModel $trackableModel;

    private MockObject&RedirectModel $redirectModel;

    private MockObject&EmailStatModel $emailStatModel;

    /**
     * @var array<array<string,string|int>>
     */
    protected array $contacts = [
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

    protected function setUp(): void
    {
        defined('MAUTIC_ENV') or define('MAUTIC_ENV', 'test');

        // Some local environments do not have ext-imap loaded, but Mailbox uses these
        // constants in method signatures and class loading fails without them.
        defined('SORTARRIVAL') or define('SORTARRIVAL', 0);
        defined('SE_UID') or define('SE_UID', 1);
        defined('FT_PEEK') or define('FT_PEEK', 2);

        $this->contactRepository    = $this->createMock(LeadRepository::class);
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->fromEmailHelper      = new FromEmailHelper($this->coreParametersHelper, $this->contactRepository);
        $this->mailbox              = $this->createMock(Mailbox::class);
        $this->logger               = $this->createMock(LoggerInterface::class);
        $this->router               = $this->createMock(RouterInterface::class);
        $this->twig                 = $this->createMock(Environment::class);
        $this->themeHelper          = $this->createMock(ThemeHelper::class);
        $this->dispatcher           = $this->createMock(EventDispatcherInterface::class);
        $this->pathsHelper          = $this->createMock(PathsHelper::class);
        $this->assetModel           = $this->createMock(AssetModel::class);
        $this->trackableModel       = $this->createMock(TrackableModel::class);
        $this->redirectModel        = $this->createMock(RedirectModel::class);
        $this->entityManager        = $this->createMock(EntityManagerInterface::class);
        $this->mailHashHelper       = new MailHashHelper($this->coreParametersHelper);
        $this->requestStack         = $this->createMock(RequestStack::class);
        $this->sMimeHelper          = $this->createMock(SMimeHelper::class);
        $this->emailStatModel       = $this->createMock(EmailStatModel::class);

        // Configure SMimeHelper to return false for signing by default
        $this->sMimeHelper->method('sMimeSigningEnabled')->willReturn(false);
        $this->sMimeHelper->method('signContent')->willReturnArgument(0);

        $this->entityManager->expects($this->never()) // Never to make sure that the mock is properly tested if needed.
            ->method('getReference');
    }

    public function testQueueModeThrowsExceptionWhenBatchLimitHit(): void
    {
        $this->expectException(BatchQueueMaxException::class);

        $this->coreParametersHelper->method('get')
            ->willReturnMap(
                [
                    ['mailer_return_path', false, null],
                    ['mailer_address_length_limit', false, 320],
                ]
            );

        $batchMailHelper = new MailHelper(
            new Mailer(new BatchTransport()),
            $this->fromEmailHelper,
            $this->coreParametersHelper,
            $this->mailbox,
            $this->logger,
            $this->mailHashHelper,
            $this->router,
            $this->twig,
            $this->themeHelper,
            $this->pathsHelper,
            $this->createMock(EventDispatcherInterface::class),
            $this->requestStack,
            $this->entityManager,
            $this->createMock(AssetModel::class),
            $this->createMock(TrackableModel::class),
            $this->createMock(RedirectModel::class),
            $this->sMimeHelper,
            $this->emailStatModel
        );
        $batchMailHelper->enableQueue();
        $batchMailHelper->addTo('somebody@somewhere.com');
        $batchMailHelper->addTo('somebodyelse@somewhere.com');
        $batchMailHelper->addTo('somebodyelse2@somewhere.com');
        $batchMailHelper->addTo('somebodyelse3@somewhere.com');
        $batchMailHelper->addTo('somebodyelse4@somewhere.com');
    }

    public function testQueueModeDisabledDoesNotThrowsExceptionWhenBatchLimitHit(): void
    {
        $this->coreParametersHelper->method('get')
            ->willReturnMap(
                [
                    ['mailer_return_path', false, null],
                ]
            );

        $entityManager = $this->createMock(EntityManagerInterface::class);

        $singleMailHelper = new MailHelper(
            new Mailer(new BcInterfaceTokenTransport()),
            $this->fromEmailHelper,
            $this->coreParametersHelper,
            $this->mailbox,
            $this->logger,
            $this->mailHashHelper,
            $this->router,
            $this->twig,
            $this->themeHelper,
            $this->pathsHelper,
            $this->createMock(EventDispatcherInterface::class),
            $this->requestStack,
            $entityManager,
            $this->createMock(AssetModel::class),
            $this->createMock(TrackableModel::class),
            $this->createMock(RedirectModel::class),
            $this->sMimeHelper,
            $this->emailStatModel,
        );

        try {
            $singleMailHelper->addTo('somebody@somewhere.com');
            $singleMailHelper->addTo('somebodyelse@somewhere.com');
        } catch (BatchQueueMaxException) {
            $this->fail('BatchQueueMaxException thrown');
        }
    }

    public function testQueuedEmailFromOverride(): void
    {
        $this->coreParametersHelper->method('get')->willReturnMap($this->defaultParams);

        $singleMailHelper = new MailHelper(
            new Mailer(new BcInterfaceTokenTransport()),
            $this->fromEmailHelper,
            $this->coreParametersHelper,
            $this->mailbox,
            $this->logger,
            $this->mailHashHelper,
            $this->router,
            $this->twig,
            $this->themeHelper,
            $this->pathsHelper,
            $this->createMock(EventDispatcherInterface::class),
            $this->requestStack,
            $this->entityManager,
            $this->createMock(AssetModel::class),
            $this->createMock(TrackableModel::class),
            $this->createMock(RedirectModel::class),
            $this->sMimeHelper,
            $this->emailStatModel,
        );
        $singleMailHelper->enableQueue();

        $email = new Email();
        $email->setFromAddress('override@nowhere.com');
        $email->setFromName('Test');
        $email->setCustomHtml('<html>{unsubscribe_url}</html>');
        $email->setUseOwnerAsMailer(false);
        $email->setSubject('Test');

        $singleMailHelper->setEmail($email);

        foreach ($this->contacts as $contact) {
            $singleMailHelper->addTo($contact['email']);
            $singleMailHelper->setLead($contact);
            $singleMailHelper->queue();
        }

        $singleMailHelper->flushQueue();
        $from = $singleMailHelper->message->getFrom();

        $this->assertCount(1, $from);
        $this->assertSame('override@nowhere.com', $from[0]->getAddress());

        $singleMailHelper->reset();
        foreach ($this->contacts as $contact) {
            $singleMailHelper->addTo($contact['email']);
            $singleMailHelper->setLead($contact);
            $singleMailHelper->queue();
        }
        $singleMailHelper->flushQueue();
        $from = $singleMailHelper->message->getFrom();

        $this->assertCount(1, $from);
        $this->assertSame('nobody@nowhere.com', $from[0]->getAddress());
    }

    public function testBatchMode(): void
    {
        $singleMailHelper = new MailHelper(
            new Mailer(new BcInterfaceTokenTransport()),
            $this->fromEmailHelper,
            $this->coreParametersHelper,
            $this->mailbox,
            $this->logger,
            $this->mailHashHelper,
            $this->router,
            $this->twig,
            $this->themeHelper,
            $this->pathsHelper,
            $this->createMock(EventDispatcherInterface::class),
            $this->requestStack,
            $this->entityManager,
            $this->createMock(AssetModel::class),
            $this->createMock(TrackableModel::class),
            $this->createMock(RedirectModel::class),
            $this->sMimeHelper,
            $this->emailStatModel,
        );
        $singleMailHelper->enableQueue();

        $email = new Email();
        $email->setSubject('Hello');
        $email->setCustomHtml('<html>{unsubscribe_url}</html>');
        $singleMailHelper->setEmail($email);

        $singleMailHelper->addTo($this->contacts[0]['email']);
        $singleMailHelper->setLead($this->contacts[0]);
        $singleMailHelper->queue();
        $singleMailHelper->flushQueue();
        $errors = $singleMailHelper->getErrors();
        $this->assertArrayNotHasKey('failures', $errors, var_export($errors, true));

        $singleMailHelper->reset(false);
        $singleMailHelper->setEmail($email);
        $singleMailHelper->addTo($this->contacts[1]['email']);
        $singleMailHelper->setLead($this->contacts[1]);
        $singleMailHelper->queue();
        $singleMailHelper->flushQueue();
        $errors = $singleMailHelper->getErrors();
        $this->assertArrayNotHasKey('failures', $errors, var_export($errors, true));
    }

    public function testQueuedOwnerAsMailer(): void
    {
        $this->coreParametersHelper->method('get')->willReturnMap($this->defaultParams);

        $this->contactRepository->method('getLeadOwner')
            ->willReturnOnConsecutiveCalls(
                ['email' => 'owner1@owner.com', 'first_name' => 'owner 1', 'last_name' => null, 'signature' => 'owner 1'],
                ['email' => 'owner2@owner.com', 'first_name' => 'owner 2', 'last_name' => null, 'signature' => 'owner 2'],
            );
        $transport     = new BatchTransport();
        $symfonyMailer = new Mailer($transport);

        $mailer = new MailHelper(
            $symfonyMailer,
            $this->fromEmailHelper,
            $this->coreParametersHelper,
            $this->mailbox,
            $this->logger,
            $this->mailHashHelper,
            $this->router,
            $this->twig,
            $this->themeHelper,
            $this->pathsHelper,
            $this->createMock(EventDispatcherInterface::class),
            $this->requestStack,
            $this->entityManager,
            $this->createMock(AssetModel::class),
            $this->createMock(TrackableModel::class),
            $this->createMock(RedirectModel::class),
            $this->sMimeHelper,
            $this->emailStatModel,
        );

        $email = new Email();
        $email->setCustomHtml('<html>{unsubscribe_url}</html>');
        $email->setUseOwnerAsMailer(true);
        $email->setSubject('Subject');
        $email->setCustomHtml('content');

        $mailer->setEmail($email);
        $mailer->enableQueue();

        foreach ($this->contacts as $contact) {
            $mailer->addTo($contact['email']);
            $mailer->setLead($contact);
            $mailer->queue();
        }

        $mailer->flushQueue([]);

        $this->assertEmpty($mailer->getErrors()['failures']);

        $fromAddresses = $transport->getFromAddresses();
        $metadatas     = $transport->getMetadatas();

        $this->assertCount(3, $fromAddresses);
        $this->assertCount(3, $metadatas);
        $this->assertEquals(['owner1@owner.com', 'nobody@nowhere.com', 'owner2@owner.com'], $fromAddresses);

        foreach ($metadatas as $key => $metadata) {
            $this->assertTrue(isset($metadata[$this->contacts[$key]['email']]));

            if (0 === $key) {
                // Should have two contacts
                $this->assertCount(2, $metadata);
                $this->assertTrue(isset($metadata['contact4@somewhere.com']));
            } else {
                $this->assertCount(1, $metadata);
            }

            // Check that signatures are valid
            if (1 === $key) {
                // signature should be empty
                $this->assertEquals('', $metadata['contact2@somewhere.com']['tokens']['{signature}']);
            } else {
                $this->assertEquals($metadata[$this->contacts[$key]['email']]['tokens']['{signature}'], 'owner '.$this->contacts[$key]['owner_id']);

                if (0 === $key) {
                    // Ensure the last contact has the correct signature
                    $this->assertEquals($metadata['contact4@somewhere.com']['tokens']['{signature}'], 'owner '.$this->contacts[$key]['owner_id']);
                }
            }
        }

        // Validate that the message object only has the contacts for the last "from" group to ensure we aren't sending duplicates
        $this->assertEquals('contact3@somewhere.com', $mailer->message->getTo()[0]->getAddress());
    }

    public function testMailAsOwnerWithEncodedCharactersInName(): void
    {
        $this->coreParametersHelper->method('get')
            ->willReturnMap([
                ['mailer_from_email', null, 'nobody@nowhere.com'],
                ['mailer_from_name', null, 'No Body&#39;s Business'],
            ]);

        $this->contactRepository->method('getLeadOwner')
            ->willReturnOnConsecutiveCalls(
                ['id' => 1, 'email' => 'owner1@owner.com', 'first_name' => 'owner 1', 'last_name' => '', 'signature' => 'owner 1'],
                ['id' => 2, 'email' => 'owner2@owner.com', 'first_name' => 'owner 2', 'last_name' => '', 'signature' => 'owner 2'],
            );

        $transport     = new BatchTransport();
        $symfonyMailer = new Mailer($transport);

        $mailer = new MailHelper(
            $symfonyMailer,
            $this->fromEmailHelper,
            $this->coreParametersHelper,
            $this->mailbox,
            $this->logger,
            $this->mailHashHelper,
            $this->router,
            $this->twig,
            $this->themeHelper,
            $this->pathsHelper,
            $this->createMock(EventDispatcherInterface::class),
            $this->requestStack,
            $this->entityManager,
            $this->createMock(AssetModel::class),
            $this->createMock(TrackableModel::class),
            $this->createMock(RedirectModel::class),
            $this->sMimeHelper,
            $this->emailStatModel,
        );
        $email  = new Email();
        $email->setCustomHtml('<html>{unsubscribe_url}</html>');
        $email->setUseOwnerAsMailer(true);

        $mailer->setEmail($email);
        $mailer->enableQueue();
        $mailer->setSubject('Hello');

        foreach ($this->contacts as $contact) {
            $mailer->addTo($contact['email']);
            $mailer->setLead($contact);
            $mailer->queue();
        }

        $mailer->flushQueue([]);

        $fromAddresses = $transport->getFromAddresses();
        $fromNames     = $transport->getFromNames();

        $this->assertCount(3, $fromAddresses); // 3, not 4, because the last contact has the same owner as the first contact.
        $this->assertCount(3, $fromNames);
        $this->assertEquals(['owner1@owner.com', 'nobody@nowhere.com', 'owner2@owner.com'], $fromAddresses);
        $this->assertEquals(['owner 1', 'No Body\'s Business', 'owner 2'], $fromNames);
    }

    public function testBatchIsEnabledWithBcTokenInterface(): void
    {
        $this->coreParametersHelper->method('get')->willReturnMap($this->defaultParams);

        $this->contactRepository->method('getLeadOwner')
            ->willReturnOnConsecutiveCalls(
                ['id' => 1, 'email' => 'owner1@owner.com', 'first_name' => 'owner 1', 'last_name' => '', 'signature' => 'owner 1'],
                ['id' => 2, 'email' => 'owner2@owner.com', 'first_name' => 'owner 2', 'last_name' => '', 'signature' => 'owner 2'],
            );
        $transport = new BatchTransport();
        $mailer    = new MailHelper(
            new Mailer($transport),
            $this->fromEmailHelper,
            $this->coreParametersHelper,
            $this->mailbox,
            $this->logger,
            $this->mailHashHelper,
            $this->router,
            $this->twig,
            $this->themeHelper,
            $this->pathsHelper,
            $this->createMock(EventDispatcherInterface::class),
            $this->requestStack,
            $this->entityManager,
            $this->createMock(AssetModel::class),
            $this->createMock(TrackableModel::class),
            $this->createMock(RedirectModel::class),
            $this->sMimeHelper,
            $this->emailStatModel,
        );
        $email = new Email();

        $email->setUseOwnerAsMailer(true);
        $email->setCustomHtml('<html>{unsubscribe_url}</html>');

        $mailer->setEmail($email);
        $mailer->enableQueue();
        $mailer->setSubject('Hello');

        foreach ($this->contacts as $contact) {
            $mailer->addTo($contact['email']);
            $mailer->setLead($contact);
            $mailer->queue();
        }

        $mailer->flushQueue([]);

        self::assertEmpty($mailer->getErrors()['failures']);

        $fromAddresses = $transport->getFromAddresses();
        $metadatas     = $transport->getMetadatas();

        $this->assertCount(3, $fromAddresses);
        $this->assertCount(3, $metadatas);
        self::assertSame(
            ['owner1@owner.com', 'nobody@nowhere.com', 'owner2@owner.com'],
            $fromAddresses
        );
    }

    public function testGlobalFromThatAllFromAddressesAreTheSame(): void
    {
        $this->contactRepository->method('getLeadOwner')
            ->willReturnOnConsecutiveCalls(
                ['id' => 1, 'email' => 'owner1@owner.com', 'first_name' => 'owner 1', 'last_name' => '', 'signature' => 'owner 1'],
                ['id' => 2, 'email' => 'owner2@owner.com', 'first_name' => 'owner 2', 'last_name' => '', 'signature' => 'owner 2'],
            );

        $transport     = new BcInterfaceTokenTransport();
        $symfonyMailer = new Mailer($transport);

        $mailer = new MailHelper(
            $symfonyMailer,
            $this->fromEmailHelper,
            $this->coreParametersHelper,
            $this->mailbox,
            $this->logger,
            $this->mailHashHelper,
            $this->router,
            $this->twig,
            $this->themeHelper,
            $this->pathsHelper,
            $this->createMock(EventDispatcherInterface::class),
            $this->requestStack,
            $this->entityManager,
            $this->createMock(AssetModel::class),
            $this->createMock(TrackableModel::class),
            $this->createMock(RedirectModel::class),
            $this->sMimeHelper,
            $this->emailStatModel,
        );
        $mailer->enableQueue();
        $mailer->setSubject('Hello');
        $mailer->setFrom('override@owner.com');

        foreach ($this->contacts as $contact) {
            $mailer->addTo($contact['email']);
            $mailer->setLead($contact);
            $mailer->queue();
        }

        $this->assertEmpty($mailer->getErrors());

        $fromAddresses = $transport->getFromAddresses();

        $this->assertEquals(['override@owner.com'], array_unique($fromAddresses));
    }

    public function testStandardEmailFrom(): void
    {
        $transport     = new SmtpTransport();
        $symfonyMailer = new Mailer($transport);
        $mailer        = new MailHelper(
            $symfonyMailer,
            $this->fromEmailHelper,
            $this->coreParametersHelper,
            $this->mailbox,
            $this->logger,
            $this->mailHashHelper,
            $this->router,
            $this->twig,
            $this->themeHelper,
            $this->pathsHelper,
            $this->createMock(EventDispatcherInterface::class),
            $this->requestStack,
            $this->entityManager,
            $this->createMock(AssetModel::class),
            $this->createMock(TrackableModel::class),
            $this->createMock(RedirectModel::class),
            $this->sMimeHelper,
            $this->emailStatModel,
        );
        $email = new Email();

        $email->setUseOwnerAsMailer(false);
        $email->setFromAddress('override@nowhere.com');
        $email->setFromName('Test');
        $email->setSubject('Subject');
        $email->setCustomHtml('content');
        $mailer->setEmail($email);

        foreach ($this->contacts as $contact) {
            $mailer->addTo($contact['email']);
            $mailer->setLead($contact);
            $mailer->setBody('{signature}');
            $mailer->send();
            $address = $mailer->message->getFrom() ? $mailer->message->getFrom()[0]->getAddress() : null;
            $this->assertEquals('override@nowhere.com', $address);
        }
    }

    public function testStandardEmailReplyTo(): void
    {
        $this->coreParametersHelper->method('get')->willReturnMap($this->defaultParams);

        $transport     = new SmtpTransport();
        $symfonyMailer = new Mailer($transport);
        $mailer        = new MailHelper(
            $symfonyMailer,
            $this->fromEmailHelper,
            $this->coreParametersHelper,
            $this->mailbox,
            $this->logger,
            $this->mailHashHelper,
            $this->router,
            $this->twig,
            $this->themeHelper,
            $this->pathsHelper,
            $this->createMock(EventDispatcherInterface::class),
            $this->requestStack,
            $this->entityManager,
            $this->createMock(AssetModel::class),
            $this->createMock(TrackableModel::class),
            $this->createMock(RedirectModel::class),
            $this->sMimeHelper,
            $this->emailStatModel,
        );
        $email = new Email();

        $email->setSubject('Subject');
        $email->setCustomHtml('<html>{unsubscribe_url}</html>');

        $mailer->setEmail($email);
        $mailer->send();
        $replyTo = $mailer->message->getReplyTo() ? $mailer->message->getReplyTo()[0]->getAddress() : null;
        $this->assertEquals('nobody@nowhere.com', $replyTo);

        $email->setReplyToAddress('replytooverride@nowhere.com');
        $mailer->setEmail($email);
        $mailer->send();
        $replyTo = $mailer->message->getReplyTo() ? $mailer->message->getReplyTo()[0]->getAddress() : null;
        $this->assertEquals('replytooverride@nowhere.com', $replyTo);
    }

    public function testEmailReplyToWithFromEmail(): void
    {
        $this->coreParametersHelper->method('get')->willReturnMap($this->defaultParams);
        $transport     = new SmtpTransport();
        $symfonyMailer = new Mailer($transport);
        $mailer        = new MailHelper(
            $symfonyMailer,
            $this->fromEmailHelper,
            $this->coreParametersHelper,
            $this->mailbox,
            $this->logger,
            $this->mailHashHelper,
            $this->router,
            $this->twig,
            $this->themeHelper,
            $this->pathsHelper,
            $this->createMock(EventDispatcherInterface::class),
            $this->requestStack,
            $this->entityManager,
            $this->createMock(AssetModel::class),
            $this->createMock(TrackableModel::class),
            $this->createMock(RedirectModel::class),
            $this->sMimeHelper,
            $this->emailStatModel,
        );
        $email = new Email();

        // From address is set
        $email->setFromAddress('from@nowhere.com');
        $email->setSubject('Subject');
        $email->setCustomHtml('content');
        $mailer->setEmail($email);
        $mailer->send();
        $replyTo = $mailer->message->getReplyTo()[0]->getAddress();
        // Expect from address in reply to
        $this->assertEquals('from@nowhere.com', $replyTo);
    }

    public function testEmailReplyToWithFromAndGlobalEmail(): void
    {
        $params = [
            ['mailer_from_email', null, 'nobody@nowhere.com'],
            ['mailer_reply_to_email', null, 'admin@mautic.com'],
        ];

        $this->coreParametersHelper->method('get')->willReturnMap($params);

        $transport     = new SmtpTransport();
        $symfonyMailer = new Mailer($transport);
        $mailer        = new MailHelper(
            $symfonyMailer,
            $this->fromEmailHelper,
            $this->coreParametersHelper,
            $this->mailbox,
            $this->logger,
            $this->mailHashHelper,
            $this->router,
            $this->twig,
            $this->themeHelper,
            $this->pathsHelper,
            $this->createMock(EventDispatcherInterface::class),
            $this->requestStack,
            $this->entityManager,
            $this->createMock(AssetModel::class),
            $this->createMock(TrackableModel::class),
            $this->createMock(RedirectModel::class),
            $this->sMimeHelper,
            $this->emailStatModel,
        );
        $email = new Email();

        // From address is set
        $email->setFromAddress('from@nowhere.com');
        $email->setSubject('Subject');
        $email->setCustomHtml('<html>{unsubscribe_url}</html>');
        $mailer->setEmail($email);
        $mailer->send();
        $replyTo = $mailer->message->getReplyTo() ? $mailer->message->getReplyTo()[0]->getAddress() : null;
        // Expect from address in reply to
        $this->assertEquals('admin@mautic.com', $replyTo);
    }

    public function testStandardOwnerAsMailer(): void
    {
        $params = [
            ['mailer_from_email', null, 'nobody@nowhere.com'],
        ];
        $this->coreParametersHelper->method('get')->willReturnMap($params);

        $this->contactRepository->method('getLeadOwner')
            ->willReturnOnConsecutiveCalls(
                ['id' => 1, 'email' => 'owner1@owner.com', 'first_name' => 'owner 1', 'last_name' => '', 'signature' => 'owner 1'],
                ['id' => 2, 'email' => 'owner2@owner.com', 'first_name' => 'owner 2', 'last_name' => '', 'signature' => 'owner 2'],
            );

        $transport     = new SmtpTransport();
        $symfonyMailer = new Mailer($transport);
        $mailer        = new MailHelper(
            $symfonyMailer,
            $this->fromEmailHelper,
            $this->coreParametersHelper,
            $this->mailbox,
            $this->logger,
            $this->mailHashHelper,
            $this->router,
            $this->twig,
            $this->themeHelper,
            $this->pathsHelper,
            $this->createMock(EventDispatcherInterface::class),
            $this->requestStack,
            $this->entityManager,
            $this->createMock(AssetModel::class),
            $this->createMock(TrackableModel::class),
            $this->createMock(RedirectModel::class),
            $this->sMimeHelper,
            $this->emailStatModel,
        );

        $email = new Email();
        $email->setUseOwnerAsMailer(true);
        $email->setSubject('Subject');
        $email->setCustomHtml('content');
        $mailer->setEmail($email);
        $mailer->setBody('{signature}');

        foreach ($this->contacts as $contact) {
            $mailer->addTo($contact['email']);
            $mailer->setLead($contact);
            $mailer->send();

            $body = $mailer->message->getHtmlBody();
            $from = $mailer->message->getFrom() ? $mailer->message->getFrom()[0]->getAddress() : null;

            if ($contact['owner_id']) {
                $this->assertEquals('owner'.$contact['owner_id'].'@owner.com', $from);
                $this->assertEquals('owner '.$contact['owner_id'], $body);
            } else {
                $this->assertEquals('nobody@nowhere.com', $from);
                $this->assertEquals('{signature}', $body); // The {signature} token is replaced in a subscriber with the current user's signature. But this is a unit test, so the subscriber doesn't run.
            }
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideEmails')]
    public function testValidateEmails(string $email, bool $isValid): void
    {
        $helper = $this->mockEmptyMailHelper();
        if (!$isValid) {
            $this->expectException(InvalidEmailException::class);
        }
        $this->assertNull($helper::validateEmail($email)); /** @phpstan-ignore-line as it's testing a deprecated method */
    }

    public function testValidateValidEmails(): void
    {
        $helper    = $this->mockEmptyMailHelper();
        $addresses = [
            'john@doe.com',
            'john@doe.email',
            'john.doe@email.com',
            'john+doe@email.com',
            'john@doe.whatevertldtheycomewithinthefuture',
        ];

        foreach ($addresses as $address) {
            // will throw InvalidEmailException if it will find the address invalid
            $this->assertNull($helper::validateEmail($address)); /** @phpstan-ignore-line as it's testing a deprecated method */
        }
    }

    /**
     * @return mixed[]
     */
    public static function provideEmails(): array
    {
        return [
            ['john@doe.com', true],
            ['john@doe.email', true],
            ['john@doe.whatevertldtheycomewithinthefuture', true],
            ['john.doe@email.com', true],
            ['john+doe@email.com', true],
            ['john@doe', false],
            ['jo hn@doe.email', false],
            ['jo^hn@doe.email', false],
            ['jo\'hn@doe.email', false],
            ['jo;hn@doe.email', false],
            ['jo&hn@doe.email', false],
            ['jo*hn@doe.email', false],
            ['jo%hn@doe.email', false],
        ];
    }

    public function testValidateEmailWithoutTld(): void
    {
        $helper = $this->mockEmptyMailHelper();
        $this->expectException(InvalidEmailException::class);
        $helper::validateEmail('john@doe'); /** @phpstan-ignore-line as it's testing a deprecated method */
    }

    public function testValidateEmailWithSpaceInIt(): void
    {
        $helper = $this->mockEmptyMailHelper();
        $this->expectException(InvalidEmailException::class);
        $helper::validateEmail('jo hn@doe.email'); /** @phpstan-ignore-line as it's testing a deprecated method */
    }

    public function testValidateEmailWithCaretInIt(): void
    {
        $helper = $this->mockEmptyMailHelper();
        $this->expectException(InvalidEmailException::class);
        $helper::validateEmail('jo^hn@doe.email'); /** @phpstan-ignore-line as it's testing a deprecated method */
    }

    public function testValidateEmailWithApostropheInIt(): void
    {
        $helper = $this->mockEmptyMailHelper();
        $this->expectException(InvalidEmailException::class);
        $helper::validateEmail('jo\'hn@doe.email'); /** @phpstan-ignore-line as it's testing a deprecated method */
    }

    public function testValidateEmailWithSemicolonInIt(): void
    {
        $helper = $this->mockEmptyMailHelper();
        $this->expectException(InvalidEmailException::class);
        $helper::validateEmail('jo;hn@doe.email'); /** @phpstan-ignore-line as it's testing a deprecated method */
    }

    public function testValidateEmailWithAmpersandInIt(): void
    {
        $helper = $this->mockEmptyMailHelper();
        $this->expectException(InvalidEmailException::class);
        $helper::validateEmail('jo&hn@doe.email'); /** @phpstan-ignore-line as it's testing a deprecated method */
    }

    public function testValidateEmailWithStarInIt(): void
    {
        $helper = $this->mockEmptyMailHelper();
        $this->expectException(InvalidEmailException::class);
        $helper::validateEmail('jo*hn@doe.email'); /** @phpstan-ignore-line as it's testing a deprecated method */
    }

    public function testValidateEmailWithPercentInIt(): void
    {
        $helper = $this->mockEmptyMailHelper();
        $this->expectException(InvalidEmailException::class);
        $helper::validateEmail('jo%hn@doe.email'); /** @phpstan-ignore-line as it's testing a deprecated method */
    }

    public function testGlobalHeadersAreSet(): void
    {
        $params = [
            ['mailer_custom_headers', [], ['X-Mautic-Test' => 'test', 'X-Mautic-Test2' => 'test']],
            ['mailer_from_email', null, 'nobody@nowhere.com'],
        ];
        $this->coreParametersHelper->method('get')->willReturnMap($params);

        $transport     = new SmtpTransport();
        $symfonyMailer = new Mailer($transport);
        $mailer        = new MailHelper(
            $symfonyMailer,
            $this->fromEmailHelper,
            $this->coreParametersHelper,
            $this->mailbox,
            $this->logger,
            $this->mailHashHelper,
            $this->router,
            $this->twig,
            $this->themeHelper,
            $this->pathsHelper,
            $this->createMock(EventDispatcherInterface::class),
            $this->requestStack,
            $this->entityManager,
            $this->createMock(AssetModel::class),
            $this->createMock(TrackableModel::class),
            $this->createMock(RedirectModel::class),
            $this->sMimeHelper,
            $this->emailStatModel,
        );
        $mailer->setBody('{signature}');
        $mailer->addTo($this->contacts[0]['email']);
        $mailer->send();

        $customHeadersFounds = [];

        /** @var array<\Symfony\Component\Mime\Header\AbstractHeader> $headers */
        $headers = $mailer->message->getHeaders()->all();
        foreach ($headers as $header) {
            if (str_contains($header->getName(), 'X-Mautic-Test')) {
                $customHeadersFounds[] = $header->getName();

                $this->assertEquals('test', $header->getBody());
            }
        }

        $this->assertCount(2, $customHeadersFounds);
    }

    public function testGlobalHeadersAreMergedIfEmailEntityIsSet(): void
    {
        $params = [
            ['mailer_custom_headers', [], ['X-Mautic-Test' => 'test', 'X-Mautic-Test2' => 'test']],
            ['mailer_from_email', null, 'nobody@nowhere.com'],
        ];
        $this->coreParametersHelper->method('get')->willReturnMap($params);
        $transport     = new SmtpTransport();
        $symfonyMailer = new Mailer($transport);
        $mailer        = new MailHelper(
            $symfonyMailer,
            $this->fromEmailHelper,
            $this->coreParametersHelper,
            $this->mailbox,
            $this->logger,
            $this->mailHashHelper,
            $this->router,
            $this->twig,
            $this->themeHelper,
            $this->pathsHelper,
            $this->createMock(EventDispatcherInterface::class),
            $this->requestStack,
            $this->entityManager,
            $this->createMock(AssetModel::class),
            $this->createMock(TrackableModel::class),
            $this->createMock(RedirectModel::class),
            $this->sMimeHelper,
            $this->emailStatModel,
        );
        $mailer->addTo($this->contacts[0]['email']);

        $email = new Email();
        $email->setSubject('Test');
        $email->setCustomHtml('{signature}');
        $mailer->setEmail($email);
        $mailer->send();

        /** @var array<HeaderInterface> $headers */
        $headers = iterator_to_array($mailer->message->getHeaders()->all());

        foreach ($headers as $header) {
            if (str_contains($header->getName(), 'X-Mautic-Test')) {
                $this->assertEquals('test', $header->getBody());
            }
        }

        $this->assertSame('test', $headers['x-mautic-test']->getBody());
        $this->assertSame('test', $headers['x-mautic-test2']->getBody());
    }

    public function testEmailHeadersAreSet(): void
    {
        $params = [
            ['mailer_custom_headers', [], ['X-Mautic-Test' => 'test', 'X-Mautic-Test2' => 'test', 'custom-mautic-header' => '{contactfield=email}', 'Reply-to' => '{contactfield=email}']],
            ['mailer_from_email', null, 'nobody@nowhere.com'],
        ];
        $this->coreParametersHelper->method('get')->willReturnMap($params);

        $transport     = new SmtpTransport();
        $symfonyMailer = new Mailer($transport);
        $mailer        = new MailHelper(
            $symfonyMailer,
            $this->fromEmailHelper,
            $this->coreParametersHelper,
            $this->mailbox,
            $this->logger,
            $this->mailHashHelper,
            $this->router,
            $this->twig,
            $this->themeHelper,
            $this->pathsHelper,
            $this->createMock(EventDispatcherInterface::class),
            $this->requestStack,
            $this->entityManager,
            $this->createMock(AssetModel::class),
            $this->createMock(TrackableModel::class),
            $this->createMock(RedirectModel::class),
            $this->sMimeHelper,
            $this->emailStatModel,
        );
        $mailer->addTo($this->contacts[0]['email']);
        $mailer->setTokens([
            '{contactfield=email}' => $this->contacts[0]['email'],
        ]);
        $email = new Email();
        $email->setSubject('Test');
        $email->setCustomHtml('{signature}');
        $email->setHeaders(['X-Mautic-Test3' => 'test2', 'X-Mautic-Test4' => 'test2']);
        $mailer->setEmail($email);
        $mailer->send();

        $customHeadersFounds = [];

        /** @var array<\Symfony\Component\Mime\Header\AbstractHeader> $headers */
        $headers = $mailer->message->getHeaders()->all();

        foreach ($headers as $header) {
            if ('X-Mautic-Test' === $header->getName() || 'X-Mautic-Test2' === $header->getName()) {
                $customHeadersFounds[] = $header->getName();
                $this->assertEquals('test', $header->getBody());
            }
            if ('X-Mautic-Test3' === $header->getName() || 'X-Mautic-Test4' === $header->getName()) {
                $customHeadersFounds[] = $header->getName();
                $this->assertEquals('test2', $header->getBody());
            }
            if ('custom-mautic-header' === $header->getName()) {
                $customHeadersFounds[] = $header->getName();
                $this->assertEquals($this->contacts[0]['email'], $header->getBody());
            }

            if ('Reply-To' === $header->getName()) {
                $customHeadersFounds[] = $header->getName();
                $this->assertCount(1, $header->getBody());
            }
        }

        $this->assertCount(6, $customHeadersFounds);
    }

    public function testUnsubscribeHeader(): void
    {
        $params = [
            ['mailer_custom_headers', [], ['X-Mautic-Test' => 'test', 'X-Mautic-Test2' => 'test']],
            ['secret_key', null, 'secret'],
        ];
        $this->coreParametersHelper->method('get')->willReturnMap($params);

        $emailSecret      = hash_hmac('sha256', 'someemail@email.test', 'secret');
        $unsubscribeUrl   = 'http://www.somedomain.cz/email/unsubscribe/hash/someemail@email.test/'.$emailSecret;
        $trackingPixelUrl = '/tracking.gif';

        $callCount = 0;
        $this->router->method('generate')
            ->willReturnCallback(function ($route, $params = []) use (&$callCount, $unsubscribeUrl, $trackingPixelUrl, $emailSecret) {
                if (0 === $callCount++) {
                    $this->assertSame('mautic_email_unsubscribe', $route);
                    $this->assertSame(['idHash' => 'hash', 'urlEmail' => 'someemail@email.test', 'secretHash' => $emailSecret], $params);

                    return $unsubscribeUrl;
                }
                $this->assertSame('mautic_email_tracker', $route);
                $this->assertSame(['idHash' => 'hash'], $params);

                return $trackingPixelUrl;
            });

        $transport     = new SmtpTransport();
        $symfonyMailer = new Mailer($transport);
        $mailer        = new MailHelper(
            $symfonyMailer,
            $this->fromEmailHelper,
            $this->coreParametersHelper,
            $this->mailbox,
            $this->logger,
            $this->mailHashHelper,
            $this->router,
            $this->twig,
            $this->themeHelper,
            $this->pathsHelper,
            $this->createMock(EventDispatcherInterface::class),
            $this->requestStack,
            $this->entityManager,
            $this->createMock(AssetModel::class),
            $this->createMock(TrackableModel::class),
            $this->createMock(RedirectModel::class),
            $this->sMimeHelper,
            $this->emailStatModel,
        );
        $mailer->setIdHash('hash');

        $email = new Email();
        $email->setSubject('Test');
        $email->setCustomHtml('<html>{unsubscribe_url}</html>');
        $lead = new Lead();
        $lead->setEmail('someemail@email.test');
        $mailer->setIdHash('hash');
        $mailer->setEmail($email);
        $mailer->setLead($lead);

        $email->setSendToDnc(false);
        $headers = $mailer->getCustomHeaders();

        $this->assertSame('<http://www.somedomain.cz/email/unsubscribe/hash/someemail@email.test/'.$emailSecret.'>', $headers['List-Unsubscribe']);
        $this->assertSame('List-Unsubscribe=One-Click', $headers['List-Unsubscribe-Post']);

        // There are no unsubscribe headers in transactional emails.
        $email->setSendToDnc(true);
        $headers = $mailer->getCustomHeaders();
        $this->assertNull($headers['List-Unsubscribe'] ?? null);
        $this->assertNull($headers['List-Unsubscribe-Post'] ?? null);
    }

    public function testUnsubscribeHeaderParameterOrder(): void
    {
        $params = [
            ['mailer_custom_headers', [],
                [
                    'X-Mautic-Test'    => 'test',
                    'X-Mautic-Test2'   => 'test',
                    'List-Unsubscribe' => '<mailto:list@host.com?subject=unsubscribe>',
                ]],
            ['secret_key', null, 'secret'],
            ['disable_unsubscribe_link_header', null, false],
        ];
        $this->coreParametersHelper->method('get')->willReturnMap($params);

        $emailSecret = hash_hmac('sha256', 'someemail@email.test', 'secret');
        $this->router->method('generate')
            ->willReturnCallback(function ($route) use ($emailSecret) {
                return 'http://www.somedomain.cz/email/unsubscribe/hash/someemail@email.test/'.$emailSecret;
            });

        $transport     = new SmtpTransport();
        $symfonyMailer = new Mailer($transport);
        $mailer        = new MailHelper(
            $symfonyMailer,
            $this->fromEmailHelper,
            $this->coreParametersHelper,
            $this->mailbox,
            $this->logger,
            $this->mailHashHelper,
            $this->router,
            $this->twig,
            $this->themeHelper,
            $this->pathsHelper,
            $this->createMock(EventDispatcherInterface::class),
            $this->requestStack,
            $this->entityManager,
            $this->createMock(AssetModel::class),
            $this->createMock(TrackableModel::class),
            $this->createMock(RedirectModel::class),
            $this->sMimeHelper,
            $this->emailStatModel,
        );
        $mailer->setIdHash('hash');

        $email = new Email();
        $email->setSubject('Test');
        $email->setCustomHtml('<html>{unsubscribe_url}</html>');
        $email->setSendToDnc(false);
        $lead = new Lead();
        $lead->setEmail('someemail@email.test');
        $mailer->setIdHash('hash');
        $mailer->setEmail($email);
        $mailer->setLead($lead);

        $headers = $mailer->getCustomHeaders();

        $this->assertSame('<http://www.somedomain.cz/email/unsubscribe/hash/someemail@email.test/'.$emailSecret.'>,<mailto:list@host.com?subject=unsubscribe>', $headers['List-Unsubscribe']);
        $this->assertSame('List-Unsubscribe=One-Click', $headers['List-Unsubscribe-Post']);
    }

    protected function mockEmptyMailHelper(): MailHelper
    {
        $transport     = new SmtpTransport();
        $symfonyMailer = new Mailer($transport);

        return new MailHelper(
            $symfonyMailer,
            $this->fromEmailHelper,
            $this->coreParametersHelper,
            $this->mailbox,
            $this->logger,
            $this->mailHashHelper,
            $this->router,
            $this->twig,
            $this->themeHelper,
            $this->pathsHelper,
            $this->createMock(EventDispatcherInterface::class),
            $this->requestStack,
            $this->entityManager,
            $this->createMock(AssetModel::class),
            $this->createMock(TrackableModel::class),
            $this->createMock(RedirectModel::class),
            $this->sMimeHelper,
            $this->emailStatModel,
        );
    }

    /**
     * @param mixed[] $parameterMap
     */
    protected function getMockLeadModel(bool $mailIsOwner = true, array $parameterMap = []): LeadModel&MockObject
    {
        $mockLeadRepository = $this->createMock(LeadRepository::class);

        $mockLeadRepository->method('getLeadOwner')
            ->willReturnMap(
                [
                    [1, ['id' => 1, 'email' => 'owner1@owner.com', 'first_name' => '', 'last_name' => '', 'signature' => 'owner 1']],
                    [2, ['id' => 2, 'email' => 'owner2@owner.com', 'first_name' => '', 'last_name' => '', 'signature' => 'owner 2']],
                    [3, ['id' => 3, 'email' => 'owner3@owner.com', 'first_name' => 'John', 'last_name' => 'S&#39;mith', 'signature' => 'owner 2']],
                ]
            );

        $mockLeadModel = $this->createMock(LeadModel::class);

        $mockLeadModel->method('getRepository')->willReturn($mockLeadRepository);

        $parameterMap = array_merge(
            [
                ['mailer_return_path', false, null],
                ['mailer_is_owner', false, $mailIsOwner],
            ],
            $parameterMap
        );

        $coreParametersHelper = $this->coreParametersHelper;

        $coreParametersHelper->method('get')->willReturnMap($parameterMap);

        $mockMailboxHelper = $this->createMock(Mailbox::class);
        $mockMailboxHelper->method('isConfigured')
            ->willReturn(false);

        return $mockLeadModel;
    }

    public function testArrayOfAddressesAreRemappedIntoEmailToNameKeyValuePair(): void
    {
        $coreParametersHelper = $this->coreParametersHelper;
        $coreParametersHelper->method('get')
            ->willReturnMap(
                [
                    ['mailer_return_path', false, null],
                ]
            );

        $symfonyMailer = new Mailer(new SmtpTransport());
        $mailer        = new MailHelper(
            $symfonyMailer,
            $this->fromEmailHelper,
            $this->coreParametersHelper,
            $this->mailbox,
            $this->logger,
            $this->mailHashHelper,
            $this->router,
            $this->twig,
            $this->themeHelper,
            $this->pathsHelper,
            $this->createMock(EventDispatcherInterface::class),
            $this->requestStack,
            $this->entityManager,
            $this->createMock(AssetModel::class),
            $this->createMock(TrackableModel::class),
            $this->createMock(RedirectModel::class),
            $this->sMimeHelper,
            $this->emailStatModel,
        );

        $mailer->setTo(['sombody@somewhere.com', 'sombodyelse@somewhere.com'], 'test');

        $emailsTo = [];

        foreach ($mailer->message->getTo() as $address) {
            $emailsTo[$address->getAddress()] = $address->getName();
        }
        $this->assertEquals(
            [
                'sombody@somewhere.com'     => 'test',
                'sombodyelse@somewhere.com' => 'test',
            ],
            $emailsTo
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('minifyHtmlDataProvider')]
    public function testMinifyHtml(bool $minifyHtml, string $html, string $expectedHtml): void
    {
        $params = [
            ['mailer_from_email', null, 'nobody@nowhere.com'],
        ];
        $params[] = ['minify_email_html', null, $minifyHtml];
        $params[] = ['mailer_is_owner', null, false];
        $params[] = ['mailer_append_tracking_pixel', null, false];
        $this->coreParametersHelper->method('get')->willReturnMap($params);
        $symfonyMailer = new Mailer(new SmtpTransport());
        $mailer        = new MailHelper(
            $symfonyMailer,
            $this->fromEmailHelper,
            $this->coreParametersHelper,
            $this->mailbox,
            $this->logger,
            $this->mailHashHelper,
            $this->router,
            $this->twig,
            $this->themeHelper,
            $this->pathsHelper,
            $this->createMock(EventDispatcherInterface::class),
            $this->requestStack,
            $this->entityManager,
            $this->createMock(AssetModel::class),
            $this->createMock(TrackableModel::class),
            $this->createMock(RedirectModel::class),
            $this->sMimeHelper,
            $this->emailStatModel,
        );

        $mailer->addTo($this->contacts[0]['email']);

        $email = new Email();
        $email->setCustomHtml($html);
        $email->setSubject('Subject');
        $mailer->setEmail($email);
        $this->assertSame($expectedHtml, $mailer->getBody(), $mailer->getBody());
    }

    /**
     * @return array<array<bool|int|string>>
     */
    public static function minifyHtmlDataProvider(): array
    {
        return [
            [false, self::MINIFY_HTML, self::MINIFY_HTML],
            [true, self::MINIFY_HTML, InputHelper::minifyHTML(self::MINIFY_HTML)],
        ];
    }

    public function testHeadersAreTokenized(): void
    {
        $this->coreParametersHelper->method('get')
            ->willReturnMap(
                [
                    ['mailer_custom_headers', [], ['X-Mautic-Test-1' => '{tracking_pixel}']],
                    ['mailer_reply_to_email', false, '{tracking_pixel}'],
                    ['mailer_from_email', null, 'nobody@nowhere.com'],
                    ['mailer_from_name', null, 'No Body'],
                    ['disable_unsubscribe_link_header', null, false],
                ]
            );

        $smtpMailHelper = new MailHelper(
            new Mailer(new SmtpTransport()),
            $this->fromEmailHelper,
            $this->coreParametersHelper,
            $this->mailbox,
            $this->logger,
            $this->mailHashHelper,
            $this->router,
            $this->twig,
            $this->themeHelper,
            $this->pathsHelper,
            $this->createMock(EventDispatcherInterface::class),
            $this->requestStack,
            $this->entityManager,
            $this->createMock(AssetModel::class),
            $this->createMock(TrackableModel::class),
            $this->createMock(RedirectModel::class),
            $this->sMimeHelper,
            $this->emailStatModel,
        );
        $smtpMailHelper->addTo($this->contacts[0]['email']);

        $email = new Email();
        $email->setSubject('Test');
        $email->setCustomHtml('<html>{unsubscribe_url}</html>');
        $email->setHeaders(['X-Mautic-Test-2' => '{tracking_pixel}']);
        $email->setSendToDnc(false);
        $smtpMailHelper->setEmail($email);
        $smtpMailHelper->send();

        /** @var iterable<MailboxListHeader> $headers */
        $headers = $smtpMailHelper->message->getHeaders()->all();

        $realHeaders = [];
        foreach ($headers as $header) {
            $realHeaders[$header->getName()] = $header->getBodyAsString();
        }

        self::assertSame(
            $realHeaders,
            [
                'To'                    => 'contact1@somewhere.com',
                'From'                  => 'No Body <nobody@nowhere.com>',
                'Sender'                => 'No Body <nobody@nowhere.com>',
                'Reply-To'              => 'nobody@nowhere.com',
                'Subject'               => 'Test',
                'X-Mautic-Test-2'       => MailHelper::getBlankPixel(),
                'X-Mautic-Test-1'       => MailHelper::getBlankPixel(),
                'List-Unsubscribe'      => '<{unsubscribe_url}>',
                'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
            ]
        );
    }

    public function testThatHtmlIsCorrectlyProcessedWhenTheAreEmbeddedImages(): void
    {
        $this->coreParametersHelper->method('get')
            ->willReturnMap(
                [
                    ['mailer_from_email', null, 'nobody@nowhere.com'],
                    ['mailer_convert_embed_images', null, true],
                    ['mailer_append_tracking_pixel', null, true],
                    ['mailer_from_name', null, 'Test'],
                    ['mailer_append_tracking_pixel', null, true],
                    ['secret_key', null, 'secret'],
                    ['site_url', null, 'https://mautic.url'],
                ]
            );

        $this->router->method('generate')->willReturn('http://tracking.url');
        $transport = new BatchTransport();
        $mailer    = new MailHelper(
            new Mailer($transport),
            $this->fromEmailHelper,
            $this->coreParametersHelper,
            $this->mailbox,
            $this->logger,
            $this->mailHashHelper,
            $this->router,
            $this->twig,
            $this->themeHelper,
            $this->pathsHelper,
            $this->createMock(EventDispatcherInterface::class),
            $this->requestStack,
            $this->entityManager,
            $this->createMock(AssetModel::class),
            $this->createMock(TrackableModel::class),
            $this->createMock(RedirectModel::class),
            $this->sMimeHelper,
            $this->emailStatModel,
        );
        $email = new Email();

        // We should use a local image to avoid network requests.
        $sampleImagePath = __DIR__.'/../../../../assets/images/avatar.png';

        $mailer->setIdHash('IDHASH');
        $email->setSubject('Test');
        $email->setCustomHtml('<img src="'.$sampleImagePath.'"> <img src="'.$sampleImagePath.'">');
        $mailer->setEmail($email);

        foreach ($this->contacts as $contact) {
            $mailer->addTo($contact['email']);
            $mailer->setLead($contact);
        }

        $mailer->send();

        $body = $transport->getMessage()->getHtmlBody();

        $this->assertStringContainsString('<img height="1" width="1" src="{tracking_pixel}" alt="" />', $body);
        $this->assertSame(2, substr_count($body, 'cid:'));

        $metadata = $transport->getMessage()->getMetadata();
        foreach ($this->contacts as $contact) {
            Assert::assertMatchesRegularExpression('#^http:\/\/tracking\.url\?ct=[A-Za-z0-9%]+$#', $metadata[$contact['email']]['tokens']['{tracking_pixel}']);
        }
    }

    public function testThatWeDontEmbedAlreadyEmbeddedImages(): void
    {
        $this->coreParametersHelper->method('get')
            ->willReturnMap(
                [
                    ['mailer_from_email', null, 'nobody@nowhere.com'],
                    ['mailer_convert_embed_images', null, true],
                    ['mailer_append_tracking_pixel', null, true],
                    ['mailer_from_name', null, 'Test'],
                    ['mailer_append_tracking_pixel', null, true],
                    ['secret_key', null, 'secret'],
                ]
            );

        $this->router->method('generate')->willReturn('http://tracking.url');

        $transport = new BatchTransport();
        $mailer    = new MailHelper(
            new Mailer($transport),
            $this->fromEmailHelper,
            $this->coreParametersHelper,
            $this->mailbox,
            $this->logger,
            $this->mailHashHelper,
            $this->router,
            $this->twig,
            $this->themeHelper,
            $this->pathsHelper,
            $this->createMock(EventDispatcherInterface::class),
            $this->requestStack,
            $this->entityManager,
            $this->createMock(AssetModel::class),
            $this->createMock(TrackableModel::class),
            $this->createMock(RedirectModel::class),
            $this->sMimeHelper,
            $this->emailStatModel,
        );
        $email = new Email();

        $email->setUseOwnerAsMailer(false);
        $email->setFromName('Test');
        $email->setSubject('Test');
        $email->setCustomHtml('<img src="cid:abcdefg">');
        $mailer->setEmail($email);

        foreach ($this->contacts as $contact) {
            $mailer->addTo($contact['email']);
            $mailer->setLead($contact);
        }

        $mailer->send();

        $body = $transport->getMessage()->getHtmlBody();

        $this->assertSame('<img src="cid:abcdefg"><img height="1" width="1" src="{tracking_pixel}" alt="" />', $body);
    }

    public function testAddToWithLongAddress(): void
    {
        $params = [
            ['mailer_from_email', null, 'nobody@nowhere.com'],
            ['mailer_from_name', null, 'No Body'],
            ['mailer_address_length_limit', null, 30], // Set a small address length limit for testing
        ];
        $this->coreParametersHelper->method('get')->willReturnMap($params);

        $transport     = new SmtpTransport();
        $symfonyMailer = new Mailer($transport);
        $mailer        = new MailHelper(
            $symfonyMailer,
            $this->fromEmailHelper,
            $this->coreParametersHelper,
            $this->mailbox,
            $this->logger,
            $this->mailHashHelper,
            $this->router,
            $this->twig,
            $this->themeHelper,
            $this->pathsHelper,
            $this->createMock(EventDispatcherInterface::class),
            $this->requestStack,
            $this->entityManager,
            $this->createMock(AssetModel::class),
            $this->createMock(TrackableModel::class),
            $this->createMock(RedirectModel::class),
            $this->sMimeHelper,
            $this->emailStatModel,
        );

        $longName = 'This is a very long name that exceeds the length limit';
        $email    = 'test@example.com';

        $result = $mailer->addTo($email, $longName);

        $this->assertTrue($result);

        $to = $mailer->message->getTo();
        $this->assertCount(1, $to);
        $this->assertEquals($email, $to[0]->getAddress());
        $this->assertEquals('', $to[0]->getName()); // Name should be empty due to length limit

        // Test with a short name
        $shortName = 'Short Name';
        $mailer->reset();
        $result = $mailer->addTo($email, $shortName);

        $this->assertTrue($result);

        $to = $mailer->message->getTo();
        $this->assertCount(1, $to);
        $this->assertEquals($email, $to[0]->getAddress());
        $this->assertEquals($shortName, $to[0]->getName()); // Short name should be used

        // Test with long encoded name
        $longName = 'อดุลย์ ';
        $mailer->reset();
        $email = 'test@example.com';

        $result = $mailer->addTo($email, $longName);

        $this->assertTrue($result);

        $to = $mailer->message->getTo();
        $this->assertCount(1, $to);
        $this->assertEquals($email, $to[0]->getAddress());
        $this->assertEquals('', $to[0]->getName()); // Name should be empty due to length limit
    }

    public function testClearMetadataAfterSend(): void
    {
        $this->coreParametersHelper->method('get')->willReturnMap($this->defaultParams);

        $transport     = new BatchTransport();
        $symfonyMailer = new Mailer($transport);
        $mailer        = new MailHelper(
            $symfonyMailer,
            $this->fromEmailHelper,
            $this->coreParametersHelper,
            $this->mailbox,
            $this->logger,
            $this->mailHashHelper,
            $this->router,
            $this->twig,
            $this->themeHelper,
            $this->pathsHelper,
            $this->createMock(EventDispatcherInterface::class),
            $this->requestStack,
            $this->entityManager,
            $this->createMock(AssetModel::class),
            $this->createMock(TrackableModel::class),
            $this->createMock(RedirectModel::class),
            $this->sMimeHelper,
            $this->createMock(EmailStatModel::class),
        );

        $email = new Email();
        $email->setSubject('Test Subject');
        $email->setCustomHtml('Test content');
        $mailer->setEmail($email);

        // Add metadata to the message by setting lead and body
        $contact = $this->contacts[0];
        $mailer->addTo($contact['email']);
        $mailer->setLead($contact);
        $mailer->setBody('Test email body with {firstname} token');

        // Manually add metadata to verify clearing functionality
        $mailer->message->addMetadata($contact['email'], ['test' => 'metadata']);

        // Verify metadata exists before sending
        $metadataBeforeSend = $mailer->message->getMetadata();
        $this->assertCount(1, $metadataBeforeSend, 'Metadata should exist before sending');

        // Send the email
        $mailer->send();

        // Verify metadata is cleared after sending
        $metadataAfterSend = $mailer->message->getMetadata();
        $this->assertCount(0, $metadataAfterSend, 'Metadata should be cleared after sending to prevent memory leaks');
    }

    public function testSetCcWithIndexedArray(): void
    {
        $symfonyMailer = new Mailer(new SmtpTransport());
        $mailer        = new MailHelper(
            $symfonyMailer,
            $this->fromEmailHelper,
            $this->coreParametersHelper,
            $this->mailbox,
            $this->logger,
            $this->mailHashHelper,
            $this->router,
            $this->twig,
            $this->themeHelper,
            $this->pathsHelper,
            $this->createMock(EventDispatcherInterface::class),
            $this->requestStack,
            $this->entityManager,
            $this->createMock(AssetModel::class),
            $this->createMock(TrackableModel::class),
            $this->createMock(RedirectModel::class),
            $this->sMimeHelper,
            $this->emailStatModel,
        );

        $addresses = ['cc1@example.com', 'cc2@example.com'];
        $result    = $mailer->setCc($addresses, 'Default Name');

        $this->assertTrue($result);

        $cc = $mailer->message->getCc();
        $this->assertCount(2, $cc);
        $this->assertEquals('cc1@example.com', $cc[0]->getAddress());
        $this->assertEquals('Default Name', $cc[0]->getName());
        $this->assertEquals('cc2@example.com', $cc[1]->getAddress());
        $this->assertEquals('Default Name', $cc[1]->getName());
    }

    public function testSetCcWithAssociativeArray(): void
    {
        $symfonyMailer = new Mailer(new SmtpTransport());
        $mailer        = new MailHelper(
            $symfonyMailer,
            $this->fromEmailHelper,
            $this->coreParametersHelper,
            $this->mailbox,
            $this->logger,
            $this->mailHashHelper,
            $this->router,
            $this->twig,
            $this->themeHelper,
            $this->pathsHelper,
            $this->createMock(EventDispatcherInterface::class),
            $this->requestStack,
            $this->entityManager,
            $this->createMock(AssetModel::class),
            $this->createMock(TrackableModel::class),
            $this->createMock(RedirectModel::class),
            $this->sMimeHelper,
            $this->emailStatModel,
        );

        $addresses = [
            'cc1@example.com' => 'Name 1',
            'cc2@example.com' => null,
        ];
        $result = $mailer->setCc($addresses, 'Default Name');

        $this->assertTrue($result);

        $cc = $mailer->message->getCc();
        $this->assertCount(2, $cc);
        $this->assertEquals('cc1@example.com', $cc[0]->getAddress());
        $this->assertEquals('Name 1', $cc[0]->getName());
        $this->assertEquals('cc2@example.com', $cc[1]->getAddress());
        $this->assertEquals('Default Name', $cc[1]->getName());
    }

    public function testSetBccWithIndexedArray(): void
    {
        $symfonyMailer = new Mailer(new SmtpTransport());
        $mailer        = new MailHelper(
            $symfonyMailer,
            $this->fromEmailHelper,
            $this->coreParametersHelper,
            $this->mailbox,
            $this->logger,
            $this->mailHashHelper,
            $this->router,
            $this->twig,
            $this->themeHelper,
            $this->pathsHelper,
            $this->createMock(EventDispatcherInterface::class),
            $this->requestStack,
            $this->entityManager,
            $this->createMock(AssetModel::class),
            $this->createMock(TrackableModel::class),
            $this->createMock(RedirectModel::class),
            $this->sMimeHelper,
            $this->emailStatModel,
        );

        $addresses = ['bcc1@example.com', 'bcc2@example.com'];
        $result    = $mailer->setBcc($addresses, 'Default Name');

        $this->assertTrue($result);

        $bcc = $mailer->message->getBcc();
        $this->assertCount(2, $bcc);
        $this->assertEquals('bcc1@example.com', $bcc[0]->getAddress());
        $this->assertEquals('Default Name', $bcc[0]->getName());
        $this->assertEquals('bcc2@example.com', $bcc[1]->getAddress());
        $this->assertEquals('Default Name', $bcc[1]->getName());
    }

    public function testSetBccWithAssociativeArray(): void
    {
        $symfonyMailer = new Mailer(new SmtpTransport());
        $mailer        = new MailHelper(
            $symfonyMailer,
            $this->fromEmailHelper,
            $this->coreParametersHelper,
            $this->mailbox,
            $this->logger,
            $this->mailHashHelper,
            $this->router,
            $this->twig,
            $this->themeHelper,
            $this->pathsHelper,
            $this->createMock(EventDispatcherInterface::class),
            $this->requestStack,
            $this->entityManager,
            $this->createMock(AssetModel::class),
            $this->createMock(TrackableModel::class),
            $this->createMock(RedirectModel::class),
            $this->sMimeHelper,
            $this->emailStatModel,
        );

        $addresses = [
            'bcc1@example.com' => 'Name 1',
            'bcc2@example.com' => null,
        ];
        $result = $mailer->setBcc($addresses, 'Default Name');

        $this->assertTrue($result);

        $bcc = $mailer->message->getBcc();
        $this->assertCount(2, $bcc);
        $this->assertEquals('bcc1@example.com', $bcc[0]->getAddress());
        $this->assertEquals('Name 1', $bcc[0]->getName());
        $this->assertEquals('bcc2@example.com', $bcc[1]->getAddress());
        $this->assertEquals('Default Name', $bcc[1]->getName());
    }

    // Testing:
    // - images with tokens
    // - if an image does not exist, it won't break the send and such image won't be embedded.
    // - if an image is already embedded, it won't be embedded again.
    // - if an image exists on the local domain, it will be embedded.
    public function testImagesEmbeddedOnSend(): void
    {
        $this->coreParametersHelper->method('get')
            ->willReturnMap(
                [
                    ['mailer_from_email', null, 'nobody@nowhere.com'],
                    ['mailer_convert_embed_images', null, true],
                    ['mailer_append_tracking_pixel', null, true],
                    ['mailer_from_name', null, 'Test'],
                    ['mailer_append_tracking_pixel', null, true],
                    ['secret_key', null, 'secret'],
                    ['site_url', null, 'https://mautic.com'],
                ]
            );

        $this->pathsHelper->method('getRootPath')->willReturn(realpath(__DIR__.'/../../../../../'));

        $mailer = new MailHelper(
            new Mailer(new SmtpTransport()),
            $this->fromEmailHelper,
            $this->coreParametersHelper,
            $this->mailbox,
            $this->logger,
            $this->mailHashHelper,
            $this->router,
            $this->twig,
            $this->themeHelper,
            $this->pathsHelper,
            $this->dispatcher,
            $this->requestStack,
            $this->entityManager,
            $this->assetModel,
            $this->trackableModel,
            $this->redirectModel,
            $this->sMimeHelper,
            $this->emailStatModel
        );
        $mailer->addTo($this->contacts[0]['email']);
        $mailer->setIdHash();

        $initialHtml = 'Text <a href="https://mautic.com">Mautic</a> <img src="cid:abc" /> <img src="{ token }" /> <img src="https://mautic.com/app/assets/images/flags/{ country }.png"/> <img src="https://mautic.com/fake.jpg">';
        $trackedHtml = $initialHtml.'{unsubscribe_url}<img height="1" width="1" src="{tracking_pixel}" alt="" />';

        $this->dispatcher->method('dispatch')
            ->willReturnCallback(function (EmailSendEvent $event, string $eventName): EmailSendEvent {
                if (EmailEvents::EMAIL_ON_SEND === $eventName) {
                    $event->addToken('{ token }', 'https://mautic.com/app/assets/images/flags/Venezuela.png');
                    $event->addToken('{ country }', 'Venezuela');
                }

                return $event;
            });

        $this->router->method('generate')
            ->willReturnCallback(fn ($route) => match ($route) {
                'mautic_email_unsubscribe' => '/unsubscribe',
                'mautic_email_tracker'     => '/tracking.gif',
                default                    => $route,
            });

        $email = new Email();
        $email->setSubject('Test');
        $email->setCustomHtml($initialHtml.'{unsubscribe_url}');
        $mailer->setEmail($email);

        try {
            $mailer->message->getBody();
            $this->fail('The body should be empty before send');
        } catch (LogicException $e) {
            Assert::assertSame('A message must have a text or an HTML part or attachments.', $e->getMessage());
        }

        Assert::assertSame($trackedHtml, $mailer->getBody());
        $mailer->send(true);

        Assert::assertMatchesRegularExpression(
            '#^Text <a href="https://mautic\.com">Mautic</a> <img src="cid:abc" /> <img src="cid:2cb7cfd2ffccfbbbaf0e4d8891df2d79" /> <img src="cid:2cb7cfd2ffccfbbbaf0e4d8891df2d79"/> <img src="https://mautic\.com/fake\.jpg">\{unsubscribe_url\}<img height="1" width="1" src="/tracking\.gif\?ct=[A-Za-z0-9%]+" alt="" />$#',
            $mailer->message->getHtmlBody()
        );
        Assert::assertSame($trackedHtml, $mailer->getBody());
    }

    public function testEmailWithDefaultSignature(): void
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->coreParametersHelper->method('get')->willReturnMap([
            ['mailer_convert_embed_images', false, true],
            ['mailer_append_tracking_pixel', false, true],
            ['mailer_from_email', null, 'nobody@nowhere.com'],
            ['mailer_reply_to_email', null, null],
            ['mailer_from_name', null, 'No Body'],
            ['mailer_address_length_limit', null, 320],
            ['mailer_return_path', false, null],
            ['brand_name', null, null],
        ]);

        $transport     = new SmtpTransport();
        $symfonyMailer = new Mailer($transport);
        $mailer        = new MailHelper(
            $symfonyMailer,
            $this->fromEmailHelper,
            $this->coreParametersHelper,
            $this->mailbox,
            $this->logger,
            $this->mailHashHelper,
            $this->router,
            $this->twig,
            $this->themeHelper,
            $this->pathsHelper,
            $eventDispatcher,
            $this->requestStack,
            $this->entityManager,
            $this->assetModel,
            $this->trackableModel,
            $this->redirectModel,
            $this->sMimeHelper,
            $this->emailStatModel
        );
        $mailer->addTo($this->contacts[0]['email']);

        $onSendDispatchCount = 0;
        $eventDispatcher->expects(self::atLeastOnce())
            ->method('dispatch')
            ->willReturnCallback(function (object $event, ?string $eventName = null) use (&$onSendDispatchCount): object {
                if ($event instanceof EmailSendEvent && EmailEvents::EMAIL_ON_SEND === $eventName) {
                    ++$onSendDispatchCount;
                    $event->addToken('{signature}', 'Demo Signature');
                }

                return $event;
            });

        $email = new Email();
        $email->setSubject('Test');
        $email->setCustomHtml('{signature}');
        $mailer->setEmail($email);

        Assert::assertNull($mailer->message->getHtmlBody());
        $mailer->send(true);
        Assert::assertSame(1, $onSendDispatchCount);
        Assert::assertStringContainsString('Demo Signature', (string) $mailer->message->getHtmlBody());
    }
}
