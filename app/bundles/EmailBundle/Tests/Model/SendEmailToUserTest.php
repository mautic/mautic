<?php

namespace Mautic\EmailBundle\Tests\Model;

use Mautic\CoreBundle\Event\TokenReplacementEvent;
use Mautic\CoreBundle\Exception\RecordNotPublishedException;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\EmailBundle\Exception\EmailCouldNotBeSentException;
use Mautic\EmailBundle\Exception\InvalidEmailException;
use Mautic\EmailBundle\Helper\EmailValidator;
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
    private MockObject $emailModel;

    /**
     * @var MockObject|EventDispatcherInterface
     */
    private MockObject $dispatcher;

    /**
     * @var MockObject|CustomFieldValidator
     */
    private MockObject $customFieldValidator;

    /**
     * @var MockObject|EmailValidator
     */
    private MockObject $emailValidator;

    private SendEmailToUser $sendEmailToUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->emailModel           = $this->createMock(EmailModel::class);
        $this->dispatcher           = $this->createMock(EventDispatcherInterface::class);
        $this->customFieldValidator = $this->createMock(CustomFieldValidator::class);
        $this->emailValidator       = $this->createMock(EmailValidator::class);
        $this->sendEmailToUser      = new SendEmailToUser(
            $this->emailModel,
            $this->dispatcher,
            $this->customFieldValidator,
            $this->emailValidator
        );
    }

    public function testEmailNotFound(): void
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

    public function testEmailNotPublished(): void
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

    public function testSendEmailWithNoError(): void
    {
        $lead  = new Lead();
        $owner = new class extends User {
            public function getId(): int
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

        $emailSendEvent                           = new class extends EmailSendEvent {
            public int $getTokenMethodCallCounter = 0;

            public function __construct()
            {
            }

            /**
             * @param bool $includeGlobal
             *
             * @return string[]
             */
            public function getTokens($includeGlobal = true): array
            {
                ++$this->getTokenMethodCallCounter;

                return [];
            }
        };

        // Global token for Email
        $this->emailModel->expects($this->once())
            ->method('dispatchEmailSendEvent')
            ->willReturn($emailSendEvent);

        // Different handling of tokens in the To, BC, BCC fields.
        $this->customFieldValidator->expects($this->exactly(3))
            ->method('validateFieldType')
            ->withConsecutive(
                ['unpublished-field', 'email'],
                ['unpublished-field', 'email'],
                ['active-field', 'email']
            )
            ->willReturnOnConsecutiveCalls(
                $this->throwException(new RecordNotPublishedException()),
                $this->throwException(new RecordNotPublishedException()),
                null
            );

        // The event is dispatched only for valid tokens.
        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->callback(
                    function (TokenReplacementEvent $event) use ($lead) {
                        Assert::assertSame('{contactfield=active-field}', $event->getContent());
                        Assert::assertSame($lead, $event->getLead());

                        // Emulate a subscriber.
                        $event->setContent('replaced.token@email.address');

                        return true;
                    }
                ),
                EmailEvents::ON_EMAIL_ADDRESS_TOKEN_REPLACEMENT,
            );

        $this->emailValidator->expects($this->exactly(4))
            ->method('validate')
            ->withConsecutive(
                ['hello@there.com'],
                ['bob@bobek.cz'],
                ['hidden@translation.in'],
                ['{invalid-token}']
            )
            ->willReturnOnConsecutiveCalls(
                null,
                null,
                null,
                $this->throwException(new InvalidEmailException('{invalid-token}'))
            );
        // Send email method

        $this->emailModel
            ->expects($this->once())
            ->method('sendEmailToUser')
            ->will($this->returnCallback(function ($email, $users, $leadCredentials, $tokens, $assetAttachments, $saveStat, $to, $cc, $bcc): void {
                $expectedUsers = [
                    ['id' => 6],
                    ['id' => 7],
                    ['id' => 10], // owner ID
                ];
                $this->assertInstanceOf(Email::class, $email);
                $this->assertEquals($expectedUsers, $users);
                $this->assertFalse($saveStat);
                $this->assertEquals(['hello@there.com', 'bob@bobek.cz', 'default@email.com'], $to);
                $this->assertEquals([], $cc);
                $this->assertEquals([0 => 'hidden@translation.in', 2 => 'replaced.token@email.address'], $bcc);
            }));

        $config = [
            'useremail' => [
                'email' => 33,
            ],
            'user_id'  => [6, 7],
            'to_owner' => true,
            'to'       => 'hello@there.com, bob@bobek.cz, {contactfield=unpublished-field|default@email.com}, {contactfield=unpublished-field}',
            'bcc'      => 'hidden@translation.in,{invalid-token}, {contactfield=active-field}',
        ];

        $this->sendEmailToUser->sendEmailToUsers($config, $lead);

        Assert::assertSame(1, $emailSendEvent->getTokenMethodCallCounter);
    }
}
