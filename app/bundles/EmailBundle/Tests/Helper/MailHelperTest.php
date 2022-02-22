<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Exception\InvalidEmailException;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\EmailBundle\MonitoredEmail\Mailbox;
use Mautic\EmailBundle\Tests\Helper\Transport\BcInterfaceTokenTransport;
use Mautic\EmailBundle\Tests\Helper\Transport\SmtpTransport;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Model\LeadModel;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Header\HeaderInterface;

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

        $mailer->setEmail($email);
        $mailer->enableQueue();

        $mailer->setSubject('Hello');

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
        $mailer->setEmail($email);
        $email->setUseOwnerAsMailer(true);

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

    public function testValidateValidEmails()
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
            $this->assertNull($helper::validateEmail($address));
        }
    }

    public function testValidateEmailWithoutTld()
    {
        $helper = $this->mockEmptyMailHelper();
        $this->expectException(InvalidEmailException::class);
        $helper::validateEmail('john@doe');
    }

    public function testValidateEmailWithSpaceInIt()
    {
        $helper = $this->mockEmptyMailHelper();
        $this->expectException(InvalidEmailException::class);
        $helper::validateEmail('jo hn@doe.email');
    }

    public function testValidateEmailWithCaretInIt()
    {
        $helper = $this->mockEmptyMailHelper();
        $this->expectException(InvalidEmailException::class);
        $helper::validateEmail('jo^hn@doe.email');
    }

    public function testValidateEmailWithApostropheInIt()
    {
        $helper = $this->mockEmptyMailHelper();
        $this->expectException(InvalidEmailException::class);
        $helper::validateEmail('jo\'hn@doe.email');
    }

    public function testValidateEmailWithSemicolonInIt()
    {
        $helper = $this->mockEmptyMailHelper();
        $this->expectException(InvalidEmailException::class);
        $helper::validateEmail('jo;hn@doe.email');
    }

    public function testValidateEmailWithAmpersandInIt()
    {
        $helper = $this->mockEmptyMailHelper();
        $this->expectException(InvalidEmailException::class);
        $helper::validateEmail('jo&hn@doe.email');
    }

    public function testValidateEmailWithStarInIt()
    {
        $helper = $this->mockEmptyMailHelper();
        $this->expectException(InvalidEmailException::class);
        $helper::validateEmail('jo*hn@doe.email');
    }

    public function testValidateEmailWithPercentInIt()
    {
        $helper = $this->mockEmptyMailHelper();
        $this->expectException(InvalidEmailException::class);
        $helper::validateEmail('jo%hn@doe.email');
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

    public function testGlobalHeadersAreIgnoredIfEmailEntityIsSet()
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
            $this->assertFalse(strpos($header->getName(), 'X-Mautic-Test'), 'System headers were not supposed to be set');
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
            if (false !== strpos($header->getName(), 'X-Mautic-Test')) {
                $customHeadersFounds[] = $header->getName();

                $this->assertEquals('test2', $header->getBody());
            }
        }

        $this->assertCount(2, $customHeadersFounds);
    }

    protected function mockEmptyMailHelper()
    {
        $mockFactory   = $this->getMockFactory();
        $transport     =  new SmtpTransport();
        $symfonyMailer = new Mailer($transport);

        return new MailHelper($mockFactory, $symfonyMailer);
    }

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

        $mockFactory = $this->getMockBuilder(MauticFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $parameterMap = array_merge(
            [
                ['mailer_return_path', false, null],
                ['mailer_spool_type', false, 'sync'],
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
                        ['mailer_spool_type', false, 'sync'],
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
}
