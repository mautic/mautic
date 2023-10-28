<?php

namespace Mautic\EmailBundle\Tests\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Exception\InvalidEmailException;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\EmailBundle\MonitoredEmail\Mailbox;
use Mautic\EmailBundle\Tests\Helper\Transport\BcInterfaceTokenTransport;
use Mautic\EmailBundle\Tests\Helper\Transport\SmtpTransport;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Model\LeadModel;
use Monolog\Logger;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Header\HeaderInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MailHelperTest extends TestCase
{
    /**
     * @var array
     */
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

    protected function setUp(): void
    {
        defined('MAUTIC_ENV') or define('MAUTIC_ENV', 'test');
    }

    public function testBatchIsEnabledWithBcTokenInterface()
    {
        $mockFactory = $this->getMockFactory();

        $transport     = new BcInterfaceTokenTransport();
        $symfonyMailer = new Mailer($transport);

        $mailer = new MailHelper($mockFactory, $symfonyMailer, ['nobody@nowhere.com' => 'No Body']);

        $email = new Email();
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

        $this->assertEmpty($mailer->getErrors());

        $fromAddresses = $transport->getFromAddresses();
        $metadatas     = $transport->getMetadatas();

        $this->assertEquals(4, count($fromAddresses));
        $this->assertEquals(4, count($metadatas));
    }

    public function testGlobalFromThatAllFromAddressesAreTheSame()
    {
        $mockFactory = $this->getMockFactory();

        $transport     = new BcInterfaceTokenTransport();
        $symfonyMailer = new Mailer($transport);

        $mailer = new MailHelper($mockFactory, $symfonyMailer, ['nobody@nowhere.com' => 'No Body']);
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

    public function testStandardEmailFrom()
    {
        $mockFactory   = $this->getMockFactory(true);
        $transport     = new SmtpTransport();
        $symfonyMailer = new Mailer($transport);
        $mailer        = new MailHelper($mockFactory, $symfonyMailer, ['nobody@nowhere.com' => 'No Body']);
        $email         = new Email();

        $email->setUseOwnerAsMailer(false);
        $email->setFromAddress('override@nowhere.com');
        $email->setFromName('Test');
        $email->setSubject('Subject');
        $email->setCustomHtml('content');
        $mailer->setEmail($email);

        foreach ($this->contacts as $key => $contact) {
            $address = $mailer->message->getFrom() ? $mailer->message->getFrom()[0]->getAddress() : null;
            $mailer->addTo($contact['email']);
            $mailer->setLead($contact);
            $mailer->setBody('{signature}');
            $mailer->send();
            $this->assertEquals('override@nowhere.com', $address);
        }
    }

    public function testStandardEmailReplyTo()
    {
        $mockFactory   = $this->getMockFactory(true);
        $transport     = new SmtpTransport();
        $symfonyMailer = new Mailer($transport);
        $mailer        = new MailHelper($mockFactory, $symfonyMailer, ['nobody@nowhere.com' => 'No Body']);
        $email         = new Email();

        $email->setSubject('Subject');
        $email->setCustomHtml('content');

        $mailer->setEmail($email);
        $replyTo = $mailer->message->getReplyTo() ? $mailer->message->getReplyTo()[0]->getAddress() : null;
        $this->assertEquals('nobody@nowhere.com', $replyTo);

        $email->setReplyToAddress('replytooverride@nowhere.com');
        $mailer->setEmail($email);
        $replyTo = $mailer->message->getReplyTo() ? $mailer->message->getReplyTo()[0]->getAddress() : null;
        $this->assertEquals('replytooverride@nowhere.com', $replyTo);
    }

    public function testEmailReplyToWithFromEmail(): void
    {
        $mockFactory   = $this->getMockFactory(true);
        $transport     = new SmtpTransport();
        $symfonyMailer = new Mailer($transport);
        $mailer        = new MailHelper($mockFactory, $symfonyMailer, ['nobody@nowhere.com' => 'No Body']);
        $email         = new Email();

        $email->setSubject('Subject');
        $email->setCustomHtml('content');

        // From address is set
        $email->setFromAddress('from@nowhere.com');
        $mailer->setEmail($email);
        $replyTo = $mailer->message->getReplyTo() ? $mailer->message->getReplyTo()[0]->getAddress() : null;
        // Expect from address in reply to
        $this->assertEquals('from@nowhere.com', $replyTo);
    }

    public function testEmailReplyToWithFromAndGlobalEmail(): void
    {
        $parameterMap = [
            ['mailer_reply_to_email', false, 'admin@mautic.com'],
        ];
        $factoryMock   = $this->getMockFactory(true, $parameterMap);
        $transport     = new SmtpTransport();
        $symfonyMailer = new Mailer($transport);
        $mailer        = new MailHelper($factoryMock, $symfonyMailer, ['nobody@nowhere.com' => 'No Body']);
        $email         = new Email();

        // From address is set
        $email->setFromAddress('from@nowhere.com');
        $email->setSubject('Subject');
        $email->setCustomHtml('content');
        $mailer->setEmail($email);
        $replyTo = $mailer->message->getReplyTo() ? $mailer->message->getReplyTo()[0]->getAddress() : null;
        // Expect from address in reply to
        $this->assertEquals('admin@mautic.com', $replyTo);
    }

    public function testStandardOwnerAsMailer()
    {
        $mockFactory = $this->getMockFactory();

        $transport     = new SmtpTransport();
        $symfonyMailer = new Mailer($transport);

        $mailer = new MailHelper($mockFactory, $symfonyMailer, ['nobody@nowhere.com' => 'No Body']);

        $email = new Email();
        $email->setUseOwnerAsMailer(true);
        $email->setSubject('Subject');
        $email->setCustomHtml('content');
        $mailer->setEmail($email);

        $mailer->setBody('{signature}');

        foreach ($this->contacts as $key => $contact) {
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
                $this->assertEquals('{signature}', $body);
            }
        }
    }

    /**
     * @dataProvider provideEmails
     */
    public function testValidateEmails(string $email, bool $isValid): void
    {
        $helper    = $this->mockEmptyMailHelper();
        if (!$isValid) {
            $this->expectException(InvalidEmailException::class);
        }
        /** @phpstan-ignore-next-line */
        $this->assertNull($helper::validateEmail($email));
    }

    /**
     * @return mixed[]
     */
    public function provideEmails(): array
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

    public function testGlobalHeadersAreSet()
    {
        $parameterMap = [
            ['mailer_custom_headers', [], ['X-Mautic-Test' => 'test', 'X-Mautic-Test2' => 'test']],
        ];
        $mockFactory = $this->getMockFactory(true, $parameterMap);

        $transport     = new SmtpTransport();
        $symfonyMailer = new Mailer($transport);

        $mailer = new MailHelper($mockFactory, $symfonyMailer, ['nobody@nowhere.com' => 'No Body']);
        $mailer->setBody('{signature}');
        $mailer->addTo($this->contacts[0]['email']);
        $mailer->send();

        $customHeadersFounds = [];

        /** @var array<\Symfony\Component\Mime\Header\AbstractHeader> $headers */
        $headers = $mailer->message->getHeaders()->all();
        foreach ($headers as $header) {
            if (false !== strpos($header->getName(), 'X-Mautic-Test')) {
                $customHeadersFounds[] = $header->getName();

                $this->assertEquals('test', $header->getBody());
            }
        }

        $this->assertCount(2, $customHeadersFounds);
    }

    public function testGlobalHeadersAreMergedIfEmailEntityIsSet(): void
    {
        $parameterMap = [
            ['mailer_custom_headers', [], ['X-Mautic-Test' => 'test', 'X-Mautic-Test2' => 'test']],
        ];
        $mockFactory = $this->getMockFactory(true, $parameterMap);

        $transport     = new SmtpTransport();
        $symfonyMailer = new Mailer($transport);

        $mailer = new MailHelper($mockFactory, $symfonyMailer, ['nobody@nowhere.com' => 'No Body']);
        $mailer->addTo($this->contacts[0]['email']);

        $email = new Email();
        $email->setSubject('Test');
        $email->setCustomHtml('{signature}');
        $mailer->setEmail($email);
        $mailer->send();

        /** @var array<HeaderInterface> $headers */
        $headers = $mailer->message->getHeaders()->all();

        foreach ($headers as $header) {
            if (false !== strpos($header->getName(), 'X-Mautic-Test')) {
                $this->assertEquals('test', $header->getBody());
            }
        }
    }

    public function testEmailHeadersAreSet()
    {
        $parameterMap = [
            ['mailer_custom_headers', [], ['X-Mautic-Test' => 'test', 'X-Mautic-Test2' => 'test']],
        ];
        $mockFactory = $this->getMockFactory(true, $parameterMap);

        $transport     = new SmtpTransport();
        $symfonyMailer = new Mailer($transport);

        $mailer = new MailHelper($mockFactory, $symfonyMailer, ['nobody@nowhere.com' => 'No Body']);
        $mailer->addTo($this->contacts[0]['email']);

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
        }

        $this->assertCount(4, $customHeadersFounds);
    }

    public function testUnsubscribeHeader(): void
    {
        $mockRouter  = $this->createMock(Router::class);
        $emailSecret = hash_hmac('sha256', 'someemail@email.test', 'secret');
        $mockRouter->expects($this->once())
            ->method('generate')
            ->with('mautic_email_unsubscribe',
                ['idHash' => 'hash'],
                UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('http://www.somedomain.cz/email/unsubscribe/hash/someemail@email.test/'.$emailSecret);

        $parameterMap = [
            ['mailer_custom_headers', [], ['X-Mautic-Test' => 'test', 'X-Mautic-Test2' => 'test']],
        ];

        /** @var MockObject|MauticFactory $mockFactory */
        $mockFactory = $this->getMockFactory(true, $parameterMap);

        $mockFactory->method('getRouter')
            ->willReturnOnConsecutiveCalls($mockRouter);

        $transport     = new SmtpTransport();
        $symfonyMailer = new Mailer($transport);

        $email = new Email();
        $email->setSubject('Test');
        $email->setCustomHtml('<html></html>');

        $mailer = new MailHelper($mockFactory, $symfonyMailer, ['nobody@nowhere.com' => 'No Body']);
        $mailer->setIdHash('hash');
        $mailer->setEmail($email);
        $lead = new Lead();
        $lead->setEmail('someemail@email.test');
        $mailer->setLead($lead);

        $mailer->setEmailType(MailHelper::EMAIL_TYPE_MARKETING);
        $headers = $mailer->getCustomHeaders();
        $this->assertSame('<http://www.somedomain.cz/email/unsubscribe/hash/someemail@email.test/'.$emailSecret.'>', $headers['List-Unsubscribe']);

        // There are no unsubscribe headers in transactional emails.
        $mailer->setEmailType(MailHelper::EMAIL_TYPE_TRANSACTIONAL);
        $headers = $mailer->getCustomHeaders();
        $this->assertNull($headers['List-Unsubscribe'] ?? null);
    }

    protected function mockEmptyMailHelper(): MailHelper
    {
        $mockFactory   = $this->getMockFactory();
        $transport     = new SmtpTransport();
        $symfonyMailer = new Mailer($transport);

        return new MailHelper($mockFactory, $symfonyMailer);
    }

    /**
     * @param mixed[] $parameterMap
     *
     * @phpstan-ignore-next-line
     */
    protected function getMockFactory(bool $mailIsOwner = true, array $parameterMap = []): MauticFactory
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

    public function testArrayOfAddressesAreRemappedIntoEmailToNameKeyValuePair()
    {
        $mockFactory = $this->getMockBuilder(MauticFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockFactory->method('getParameter')
            ->will(
                $this->returnValueMap(
                    [
                        ['mailer_return_path', false, null],
                    ]
                )
            );
        $mockLogger = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockFactory->method('getLogger')
            ->willReturn($mockLogger);

        $symfonyMailer = new Mailer(new SmtpTransport());

        $mailer = new MailHelper($mockFactory, $symfonyMailer, ['nobody@nowhere.com' => 'No Body']);

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

    /**
     * @dataProvider minifyHtmlDataProvider
     */
    public function testMinifyHtml(bool $minifyHtml, string $html, string $expectedHtml): void
    {
        $mockFactory = $this->getMockFactory(
            true,
            [
                ['minify_email_html', false, $minifyHtml],
                ['mailer_is_owner', false, false],
                ['mailer_append_tracking_pixel', false, false],
            ]
        );
        $mailer = new Mailer(new SmtpTransport());

        $mailer = new MailHelper($mockFactory, $mailer, ['nobody@nowhere.com' => 'No Body']);
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
    public function minifyHtmlDataProvider(): array
    {
        $html = '<!doctype html>
<html lang=3D"en" xmlns=3D"http://www.w3.org/1999/xhtml" xmlns:v=3D"urn:schemas-microsoft-com:vml" xmlns:o=3D"urn:schemas-microsoft-com:office:office">
  <head>
    <title>Test</title>
    <body style=3D"word-spacing:normal;background-color:#FFFFFF;">
        <div  style=3D"background:#FFFFFF;background-color:#FFFFFF;margin:0pxauto;max-width:600px;">
    </body>
</html>';

        return [
            [false, $html, $html],
            [true, $html, InputHelper::minifyHTML($html)],
        ];
    }
}
