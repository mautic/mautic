<?php

namespace Mautic\LeadBundle\Tests\EventListener;

use Mautic\CoreBundle\Event\TokenReplacementEvent;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\EmailBundle\Event\EmailBuilderEvent;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\EmailBundle\Helper\FromEmailHelper;
use Mautic\EmailBundle\Helper\MailHashHelper;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\EmailBundle\MonitoredEmail\Mailbox;
use Mautic\EmailBundle\Tests\Helper\Transport\SmtpTransport;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\EventListener\OwnerSubscriber;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\UserBundle\Entity\User;
use Monolog\Logger;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class OwnerSubscriberTest extends TestCase
{
    protected $contacts = [
        [
            'id'        => 1,
            'email'     => 'contact1@somewhere.com',
            'firstname' => 'Contact',
            'lastname'  => '1',
            'owner_id'  => 3,
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
        [
            'id'        => 5,
            'email'     => 'contact5@somewhere.com',
            'firstname' => 'Contact',
            'lastname'  => '5',
            'owner_id'  => null,
        ],
    ];

    /** @var MockObject&CoreParametersHelper */
    private $coreParametersHelper;

    private MailHashHelper $mailHashHelper;

    public function setUp(): void
    {
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->mailHashHelper       = new MailHashHelper($this->coreParametersHelper);
    }

    public function testOnEmailBuild(): void
    {
        $leadModel = $this->getMockFactory()->getModel('lead');
        if (!$leadModel instanceof LeadModel) {
            self::fail('The mock does not contain LeadModel.');
        }
        $subscriber = new OwnerSubscriber($leadModel, $this->getMockTranslator());
        $event      = new EmailBuilderEvent($this->getMockTranslator());
        $subscriber->onEmailBuild($event);

        $tokens = $event->getTokens();
        $this->assertArrayHasKey('{ownerfield=email}', $tokens);
        $this->assertArrayHasKey('{ownerfield=firstname}', $tokens);
        $this->assertArrayHasKey('{ownerfield=lastname}', $tokens);
    }

    public function testOnEmailGenerate(): void
    {
        $leadModel = $this->getMockFactory()->getModel('lead');
        if (!$leadModel instanceof LeadModel) {
            self::fail('The mock does not contain LeadModel.');
        }
        $subscriber = new OwnerSubscriber($leadModel, $this->getMockTranslator());

        $mailer = $this->getMockMailer($this->contacts[0]);
        $event  = $this->getEmailSendEvent($mailer);
        $subscriber->onEmailGenerate($event);

        $tokens = $event->getTokens();

        $this->assertArrayNotHasKey('{ownerfield=email}', $tokens);
        $this->assertArrayHasKey('{ownerfield=firstname}', $tokens);
        $this->assertArrayHasKey('{ownerfield=lastname}', $tokens);

        $this->assertEquals('John', $tokens['{ownerfield=firstname}']);
        $this->assertEquals('S&#39;mith', $tokens['{ownerfield=lastname}']);
    }

    public function testOnEmailGenerateWithFakeOwner(): void
    {
        $leadModel = $this->getMockFactory()->getModel('lead');
        if (!$leadModel instanceof LeadModel) {
            self::fail('The mock does not contain LeadModel.');
        }
        $subscriber = new OwnerSubscriber($leadModel, $this->getMockTranslator());

        $mailer = $this->getMockMailer($this->contacts[1]);
        $event  = $this->getEmailSendEvent($mailer);
        $subscriber->onEmailGenerate($event);

        $tokens = $event->getTokens();
        $this->assertArrayHasKey('{ownerfield=email}', $tokens);
        $this->assertArrayHasKey('{ownerfield=firstname}', $tokens);
        $this->assertArrayHasKey('{ownerfield=lastname}', $tokens);
    }

    public function testOnEmailGenerateWithNoOwner(): void
    {
        $leadModel = $this->getMockFactory()->getModel('lead');
        if (!$leadModel instanceof LeadModel) {
            self::fail('The mock does not contain LeadModel.');
        }
        $subscriber = new OwnerSubscriber($leadModel, $this->getMockTranslator());

        $mailer = $this->getMockMailer($this->contacts[4]);
        $event  = $this->getEmailSendEvent($mailer);
        $subscriber->onEmailGenerate($event);

        $tokens = $event->getTokens();
        $this->assertArrayHasKey('{ownerfield=email}', $tokens);
        $this->assertArrayHasKey('{ownerfield=firstname}', $tokens);
        $this->assertArrayHasKey('{ownerfield=lastname}', $tokens);

        $this->assertEquals('', $tokens['{ownerfield=email}']);
        $this->assertEquals('', $tokens['{ownerfield=firstname}']);
        $this->assertEquals('', $tokens['{ownerfield=lastname}']);
    }

    public function testOnEmailDisplay(): void
    {
        $leadModel = $this->getMockFactory()->getModel('lead');
        if (!$leadModel instanceof LeadModel) {
            self::fail('The mock does not contain LeadModel.');
        }
        $subscriber = new OwnerSubscriber($leadModel, $this->getMockTranslator());

        $mailer = $this->getMockMailer($this->contacts[0]);
        $event  = $this->getEmailSendEvent($mailer);
        $subscriber->onEmailDisplay($event);

        $tokens = $event->getTokens();
        $this->assertArrayNotHasKey('{ownerfield=email}', $tokens);
        $this->assertArrayHasKey('{ownerfield=firstname}', $tokens);
        $this->assertArrayHasKey('{ownerfield=lastname}', $tokens);
    }

    public function testOnEmailDisplayWithFakeOwner(): void
    {
        $leadModel = $this->getMockFactory()->getModel('lead');
        if (!$leadModel instanceof LeadModel) {
            self::fail('The mock does not contain LeadModel.');
        }
        $subscriber = new OwnerSubscriber($leadModel, $this->getMockTranslator());

        $mailer = $this->getMockMailer($this->contacts[1]);
        $event  = $this->getEmailSendEvent($mailer);
        $subscriber->onEmailDisplay($event);

        $tokens = $event->getTokens();
        $this->assertArrayHasKey('{ownerfield=email}', $tokens);
        $this->assertArrayHasKey('{ownerfield=firstname}', $tokens);
        $this->assertArrayHasKey('{ownerfield=lastname}', $tokens);
    }

    public function testOnEmailDisplayWithNoOwner(): void
    {
        $leadModel = $this->getMockFactory()->getModel('lead');
        if (!$leadModel instanceof LeadModel) {
            self::fail('The mock does not contain LeadModel.');
        }
        $subscriber = new OwnerSubscriber($leadModel, $this->getMockTranslator());

        $mailer = $this->getMockMailer($this->contacts[4]);
        $event  = $this->getEmailSendEvent($mailer);
        $subscriber->onEmailDisplay($event);

        $tokens = $event->getTokens();
        $this->assertArrayHasKey('{ownerfield=email}', $tokens);
        $this->assertArrayHasKey('{ownerfield=firstname}', $tokens);
        $this->assertArrayHasKey('{ownerfield=lastname}', $tokens);

        $this->assertEquals('', $tokens['{ownerfield=email}']);
        $this->assertEquals('', $tokens['{ownerfield=firstname}']);
        $this->assertEquals('', $tokens['{ownerfield=lastname}']);
    }

    /**
     * @param mixed[] $parameterMap
     */
    protected function getMockFactory(bool $mailIsOwner = true, array $parameterMap = []): MauticFactory|MockObject
    {
        $mockLeadRepository = $this->getMockBuilder(LeadRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockLeadRepository->method('getLeadOwner')
            ->will(
                $this->returnValueMap(
                    [
                        [1, ['id' => 1, 'email' => 'owner1@owner.com', 'first_name' => '', 'last_name' => '', 'signature' => 'owner 1']],
                        [2, ['id' => 2, 'email' => 'owner2@owner.com', 'first_name' => '', 'last_name' => '', 'signature' => 'owner 2']],
                        [3, ['id' => 3, 'email' => 'owner3@owner.com', 'first_name' => 'John', 'last_name' => 'S&#39;mith', 'signature' => 'owner 2']],
                    ]
                )
            );

        $mockLeadModel = $this->getMockBuilder(LeadModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockLeadModel->method('getRepository')
            ->willReturn($mockLeadRepository);

        /** @var MauticFactory&MockObject $mockFactory */
        $mockFactory = $this->getMockBuilder(MauticFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $parameterMap = array_merge(
            [
                ['mailer_return_path', false, null],
                ['mailer_is_owner', false, $mailIsOwner],
            ],
            $parameterMap
        );

        $mockFactory->method('getParameter')
            ->will(
                $this->returnValueMap($parameterMap)
            );
        $mockFactory->method('getModel')
            ->will(
                $this->returnValueMap(
                    [
                        ['lead', $mockLeadModel],
                    ]
                )
            );

        $mockLogger = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockFactory->method('getLogger')
            ->willReturn($mockLogger);

        $mockMailboxHelper = $this->getMockBuilder(Mailbox::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockMailboxHelper->method('isConfigured')
            ->willReturn(false);

        $mockFactory->method('getHelper')
            ->will(
                $this->returnValueMap(
                    [
                        ['mailbox', $mockMailboxHelper],
                    ]
                )
            );

        return $mockFactory;
    }

    protected function getMockMailer(array $lead): MailHelper
    {
        $parameterMap = [
            ['mailer_custom_headers', [], ['X-Mautic-Test' => 'test', 'X-Mautic-Test2' => 'test']],
        ];
        $mockFactory = $this->getMockFactory(true, $parameterMap);

        /** @var FromEmailHelper|MockObject $fromEmaiHelper */
        $fromEmaiHelper = $this->createMock(FromEmailHelper::class);

        /** @var CoreParametersHelper|MockObject $coreParametersHelper */
        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);

        /** @var Mailbox|MockObject $mailbox */
        $mailbox = $this->createMock(Mailbox::class);

        /** @var LoggerInterface|MockObject $logger */
        $logger = $this->createMock(LoggerInterface::class);

        /** @var MockObject&RouterInterface $router */
        $router = $this->createMock(RouterInterface::class);

        $transport    = new SmtpTransport();
        $mailer       = new Mailer($transport);
        $mailerHelper = new MailHelper($mockFactory, $mailer, $fromEmaiHelper, $coreParametersHelper, $mailbox, $logger, $this->mailHashHelper, $router);
        $mailerHelper->setLead($lead);

        return $mailerHelper;
    }

    /**
     * @return Translator&MockObject
     */
    protected function getMockTranslator()
    {
        /** @var Translator&MockObject $translator */
        $translator = $this->createMock(Translator::class);
        $translator->expects($this->any())
            ->method('hasId')
            ->will($this->returnValue(false));

        return $translator;
    }

    /**
     * @dataProvider onSmsTokenReplacementProvider
     */
    public function testOnSmsTokenReplacement(string $content, string $expected, Lead $lead): void
    {
        $leadModel      = $this->createMock(LeadModel::class);
        $leadRepository = $this->createMock(LeadRepository::class);
        $leadRepository->method('getLeadOwner')->willReturn(['first_name' => 'John', 'last_name' => 'Doe']);
        $leadModel->method('getRepository')->willReturn($leadRepository);
        $translator = $this->createMock(TranslatorInterface::class);
        $subscriber = new OwnerSubscriber($leadModel, $translator);

        $event = new TokenReplacementEvent($content, $lead);
        $subscriber->onSmsTokenReplacement($event);
        $this->assertEquals($expected, $event->getContent());
    }

    protected function getUser(): User
    {
        $user = new class() extends User {
            public function setId(int $id): void
            {
                $this->id = $id;
            }
        };
        $user->setId(1);
        $user->setFirstName('John');
        $user->setLastName('Doe');

        return $user;
    }

    /**
     * @return array<mixed>
     */
    public function onSmsTokenReplacementProvider(): array
    {
        $lead = $this->getMockBuilder(Lead::class)
            ->getMock();
        $lead->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $lead->expects($this->any())
            ->method('getProfileFields')
            ->willReturn(
                [
                    'id'     => 1,
                ]
            );
        $lead->expects($this->any())
            ->method('getowner')
            ->willReturn(
                $this->getUser()
            );
        $user = $this->getUser();
        $lead->setOwner($user);
        $validOwner = [
            'Hello {ownerfield=firstname} {ownerfield=lastname}',
            'Hello John Doe',
            $lead,
        ];

        $noOwner = [
            'Hello {ownerfield=firstname} {ownerfield=lastname}',
            'Hello  ',
            new Lead(),
        ];

        return [
            $validOwner,
            $noOwner,
        ];
    }

    protected function getEmailSendEvent(MailHelper $mailer): EmailSendEvent
    {
        $event = new EmailSendEvent($mailer);
        $event->setContent('<html><body>{ownerfield=firstname} {ownerfield=lastname}</body></html>');

        return $event;
    }
}
