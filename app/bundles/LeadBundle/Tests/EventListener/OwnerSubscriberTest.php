<?php

namespace Mautic\LeadBundle\Tests\EventListener;

use Mautic\CoreBundle\Event\TokenReplacementEvent;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\EmailBundle\Event\EmailBuilderEvent;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\EmailBundle\MonitoredEmail\Mailbox;
use Mautic\EmailBundle\Tests\Helper\Transport\SmtpTransport;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\EventListener\OwnerSubscriber;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\UserBundle\Entity\User;
use Monolog\Logger;
use Symfony\Contracts\Translation\TranslatorInterface;

class OwnerSubscriberTest extends \PHPUnit\Framework\TestCase
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

    public function setUp(): void
    {
        defined('MAUTIC_ENV') or define('MAUTIC_ENV', 'test');
    }

    public function testOnEmailBuild()
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

    public function testOnEmailGenerate()
    {
        $leadModel = $this->getMockFactory()->getModel('lead');
        if (!$leadModel instanceof LeadModel) {
            self::fail('The mock does not contain LeadModel.');
        }
        $subscriber = new OwnerSubscriber($leadModel, $this->getMockTranslator());

        $mailer = $this->getMockMailer($this->contacts[0]);
        $event  = new EmailSendEvent($mailer);
        $subscriber->onEmailGenerate($event);

        $tokens = $event->getTokens();

        $this->assertArrayHasKey('{ownerfield=email}', $tokens);
        $this->assertArrayHasKey('{ownerfield=firstname}', $tokens);
        $this->assertArrayHasKey('{ownerfield=lastname}', $tokens);

        $this->assertEquals('owner3@owner.com', $tokens['{ownerfield=email}']);
        $this->assertEquals('John', $tokens['{ownerfield=firstname}']);
        $this->assertEquals('S&#39;mith', $tokens['{ownerfield=lastname}']);
    }

    public function testOnEmailGenerateWithFakeOwner()
    {
        $leadModel = $this->getMockFactory()->getModel('lead');
        if (!$leadModel instanceof LeadModel) {
            self::fail('The mock does not contain LeadModel.');
        }
        $subscriber = new OwnerSubscriber($leadModel, $this->getMockTranslator());

        $mailer = $this->getMockMailer($this->contacts[1]);
        $event  = new EmailSendEvent($mailer);
        $subscriber->onEmailGenerate($event);

        $tokens = $event->getTokens();
        $this->assertArrayHasKey('{ownerfield=email}', $tokens);
        $this->assertArrayHasKey('{ownerfield=firstname}', $tokens);
        $this->assertArrayHasKey('{ownerfield=lastname}', $tokens);
    }

    public function testOnEmailGenerateWithNoOwner()
    {
        $leadModel = $this->getMockFactory()->getModel('lead');
        if (!$leadModel instanceof LeadModel) {
            self::fail('The mock does not contain LeadModel.');
        }
        $subscriber = new OwnerSubscriber($leadModel, $this->getMockTranslator());

        $mailer = $this->getMockMailer($this->contacts[4]);
        $event  = new EmailSendEvent($mailer);
        $subscriber->onEmailGenerate($event);

        $tokens = $event->getTokens();
        $this->assertArrayHasKey('{ownerfield=email}', $tokens);
        $this->assertArrayHasKey('{ownerfield=firstname}', $tokens);
        $this->assertArrayHasKey('{ownerfield=lastname}', $tokens);

        $this->assertEquals('', $tokens['{ownerfield=email}']);
        $this->assertEquals('', $tokens['{ownerfield=firstname}']);
        $this->assertEquals('', $tokens['{ownerfield=lastname}']);
    }

    public function testOnEmailDisplay()
    {
        $leadModel = $this->getMockFactory()->getModel('lead');
        if (!$leadModel instanceof LeadModel) {
            self::fail('The mock does not contain LeadModel.');
        }
        $subscriber = new OwnerSubscriber($leadModel, $this->getMockTranslator());

        $mailer = $this->getMockMailer($this->contacts[0]);
        $event  = new EmailSendEvent($mailer);
        $subscriber->onEmailDisplay($event);

        $tokens = $event->getTokens();
        $this->assertArrayHasKey('{ownerfield=email}', $tokens);
        $this->assertArrayHasKey('{ownerfield=firstname}', $tokens);
        $this->assertArrayHasKey('{ownerfield=lastname}', $tokens);
    }

    public function testOnEmailDisplayWithFakeOwner()
    {
        $leadModel = $this->getMockFactory()->getModel('lead');
        if (!$leadModel instanceof LeadModel) {
            self::fail('The mock does not contain LeadModel.');
        }
        $subscriber = new OwnerSubscriber($leadModel, $this->getMockTranslator());

        $mailer = $this->getMockMailer($this->contacts[1]);
        $event  = new EmailSendEvent($mailer);
        $subscriber->onEmailDisplay($event);

        $tokens = $event->getTokens();
        $this->assertArrayHasKey('{ownerfield=email}', $tokens);
        $this->assertArrayHasKey('{ownerfield=firstname}', $tokens);
        $this->assertArrayHasKey('{ownerfield=lastname}', $tokens);
    }

    public function testOnEmailDisplayWithNoOwner()
    {
        $leadModel = $this->getMockFactory()->getModel('lead');
        if (!$leadModel instanceof LeadModel) {
            self::fail('The mock does not contain LeadModel.');
        }
        $subscriber = new OwnerSubscriber($leadModel, $this->getMockTranslator());

        $mailer = $this->getMockMailer($this->contacts[4]);
        $event  = new EmailSendEvent($mailer);
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
     * @param bool  $mailIsOwner
     * @param array $parameterMap
     *
     * @return MauticFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockFactory($mailIsOwner = true, $parameterMap = [])
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

        /** @var MauticFactory|\PHPUnit_Framework_MockObject_MockObject $mockFactory */
        $mockFactory = $this->getMockBuilder(MauticFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $parameterMap = array_merge(
            [
                ['mailer_return_path', false, null],
                ['mailer_spool_type', false, 'memory'],
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

    protected function getMockMailer(array $lead)
    {
        $parameterMap = [
            ['mailer_custom_headers', [], ['X-Mautic-Test' => 'test', 'X-Mautic-Test2' => 'test']],
        ];
        /** @var MauticFactory $mockFactory */
        $mockFactory = $this->getMockFactory(true, $parameterMap);

        $transport   = new SmtpTransport();
        $swiftMailer = new \Swift_Mailer($transport);
        $mailer      = new MailHelper($mockFactory, $swiftMailer, ['nobody@nowhere.com' => 'No Body']);
        $mailer->setLead($lead);

        return $mailer;
    }

    /**
     * @return Translator|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockTranslator()
    {
        /** @var Translator|\PHPUnit_Framework_MockObject_MockObject $translator */
        $translator = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->getMock();
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

    /**
     * @return User
     */
    protected function getUser()
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
}
