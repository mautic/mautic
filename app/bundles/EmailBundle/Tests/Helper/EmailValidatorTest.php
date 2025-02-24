<?php

namespace Mautic\EmailBundle\Tests\Helper;

use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailValidationEvent;
use Mautic\EmailBundle\Exception\InvalidEmailException;
use Mautic\EmailBundle\Helper\EmailValidator;
use Mautic\EmailBundle\Tests\Helper\EventListener\EmailValidationSubscriber;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Contracts\Translation\TranslatorInterface;

class EmailValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject&TranslatorInterface
     */
    private MockObject $translator;

    /**
     * @var MockObject&EventDispatcherInterface
     */
    private MockObject $dispatcher;

    /**
     * @var MockObject&EmailValidationEvent
     */
    private MockObject $event;

    private EmailValidator $emailValidator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->event      = $this->createMock(EmailValidationEvent::class);

        $this->translator->method('trans')->willReturn('some translation');

        $this->emailValidator = new EmailValidator($this->translator, $this->dispatcher);
    }

    public function testValidGmailEmail(): void
    {
        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(EmailValidationEvent::class), EmailEvents::ON_EMAIL_VALIDATION)
            ->willReturn($this->event);

        $this->event->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $this->emailValidator->validate('john@gmail.com');
    }

    public function testValidGmailEmailWithPeriod(): void
    {
        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(EmailValidationEvent::class), EmailEvents::ON_EMAIL_VALIDATION)
            ->willReturn($this->event);

        $this->event->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $this->emailValidator->validate('john.doe@gmail.com');
    }

    public function testValidGmailEmailWithPlus(): void
    {
        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(EmailValidationEvent::class), EmailEvents::ON_EMAIL_VALIDATION)
            ->willReturn($this->event);

        $this->event->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $this->emailValidator->validate('john+doe@gmail.com');
    }

    public function testValidGmailEmailWithNonStandardTld(): void
    {
        $this->dispatcher->expects($this->once())
        ->method('dispatch')
        ->with($this->isInstanceOf(EmailValidationEvent::class), EmailEvents::ON_EMAIL_VALIDATION)
        ->willReturn($this->event);

        $this->event->expects($this->once())
        ->method('isValid')
        ->willReturn(true);

        // hopefully this domain remains intact
        $this->emailValidator->validate('john@mail.email');
    }

    public function testValidateNull(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->emailValidator->validate(null);
    }

    public function testValidateEmailWithoutTld(): void
    {
        $this->expectException(InvalidEmailException::class);
        $this->emailValidator->validate('john@doe');
    }

    public function testValidateEmailWithSpaceInIt(): void
    {
        $this->expectException(InvalidEmailException::class);
        $this->emailValidator->validate('jo hn@gmail.com');
    }

    public function testValidateEmailWithCaretInIt(): void
    {
        $this->expectException(InvalidEmailException::class);
        $this->emailValidator->validate('jo^hn@gmail.com');
    }

    public function testValidateEmailWithApostropheInTheDomainPortion(): void
    {
        $this->expectException(InvalidEmailException::class);
        $this->emailValidator->validate('john@gm\'ail.com');
    }

    public function testValidateEmailWithSemicolonInIt(): void
    {
        $this->expectException(InvalidEmailException::class);
        $this->emailValidator->validate('jo;hn@gmail.com');
    }

    public function testValidateEmailWithAmpersandInIt(): void
    {
        $this->expectException(InvalidEmailException::class);
        $this->emailValidator->validate('jo&hn@gmail.com');
    }

    public function testValidateEmailWithStarInIt(): void
    {
        $this->expectException(InvalidEmailException::class);
        $this->emailValidator->validate('jo*hn@gmail.com');
    }

    public function testValidateEmailWithPercentInIt(): void
    {
        $this->expectException(InvalidEmailException::class);
        $this->emailValidator->validate('jo%hn@gmail.com');
    }

    public function testValidateEmailWithDoublePeriodInIt(): void
    {
        $this->expectException(InvalidEmailException::class);
        $this->emailValidator->validate('jo..hn@gmail.com');
    }

    public function testValidateEmailWithBadDNS(): void
    {
        $this->expectException(InvalidEmailException::class);
        $this->emailValidator->validate('john@doe.shouldneverexist', true);
    }

    public function testIntegrationInvalidatesEmail(): void
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new EmailValidationSubscriber());

        $emailValidator = new EmailValidator($this->translator, $dispatcher);

        $this->expectException(InvalidEmailException::class);
        $this->expectExceptionMessage('bad email');

        $emailValidator->doPluginValidation('bad@gmail.com');
    }
}
