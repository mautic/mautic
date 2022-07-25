<?php

namespace Mautic\EmailBundle\Tests\Helper;

use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailValidationEvent;
use Mautic\EmailBundle\Exception\InvalidEmailException;
use Mautic\EmailBundle\Helper\EmailValidator;
use Mautic\EmailBundle\Tests\Helper\EventListener\EmailValidationSubscriber;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Translation\Translator;

class EmailValidatorTest extends \PHPUnit\Framework\TestCase
{
    private $translator;
    private $dispatcher;
    private $event;
    private $emailValidator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator = $this->createMock(Translator::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->event      = $this->createMock(EmailValidationEvent::class);

        $this->emailValidator = new EmailValidator($this->translator, $this->dispatcher);
    }

    public function testValidGmailEmail()
    {
        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(EmailEvents::ON_EMAIL_VALIDATION)
            ->willReturn($this->event);

        $this->event->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $this->emailValidator->validate('john@gmail.com');
    }

    public function testValidGmailEmailWithPeriod()
    {
        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(EmailEvents::ON_EMAIL_VALIDATION)
            ->willReturn($this->event);

        $this->event->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $this->emailValidator->validate('john.doe@gmail.com');
    }

    public function testValidGmailEmailWithPlus()
    {
        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(EmailEvents::ON_EMAIL_VALIDATION)
            ->willReturn($this->event);

        $this->event->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $this->emailValidator->validate('john+doe@gmail.com');
    }

    public function testValidGmailEmailWithNonStandardTld()
    {
        $this->dispatcher->expects($this->once())
        ->method('dispatch')
        ->with(EmailEvents::ON_EMAIL_VALIDATION)
        ->willReturn($this->event);

        $this->event->expects($this->once())
        ->method('isValid')
        ->willReturn(true);

        // hopefully this domain remains intact
        $this->emailValidator->validate('john@mail.email');
    }

    public function testValidateEmailWithoutTld()
    {
        $this->expectException(InvalidEmailException::class);
        $this->emailValidator->validate('john@doe');
    }

    public function testValidateEmailWithSpaceInIt()
    {
        $this->expectException(InvalidEmailException::class);
        $this->emailValidator->validate('jo hn@gmail.com');
    }

    public function testValidateEmailWithCaretInIt()
    {
        $this->expectException(InvalidEmailException::class);
        $this->emailValidator->validate('jo^hn@gmail.com');
    }

    public function testValidateEmailWithApostropheInTheDomainPortion()
    {
        $this->expectException(InvalidEmailException::class);
        $this->emailValidator->validate('john@gm\'ail.com');
    }

    public function testValidateEmailWithSemicolonInIt()
    {
        $this->expectException(InvalidEmailException::class);
        $this->emailValidator->validate('jo;hn@gmail.com');
    }

    public function testValidateEmailWithAmpersandInIt()
    {
        $this->expectException(InvalidEmailException::class);
        $this->emailValidator->validate('jo&hn@gmail.com');
    }

    public function testValidateEmailWithStarInIt()
    {
        $this->expectException(InvalidEmailException::class);
        $this->emailValidator->validate('jo*hn@gmail.com');
    }

    public function testValidateEmailWithPercentInIt()
    {
        $this->expectException(InvalidEmailException::class);
        $this->emailValidator->validate('jo%hn@gmail.com');
    }

    public function testValidateEmailWithDoublePeriodInIt()
    {
        $this->expectException(InvalidEmailException::class);
        $this->emailValidator->validate('jo..hn@gmail.com');
    }

    public function testValidateEmailWithBadDNS()
    {
        $this->expectException(InvalidEmailException::class);
        $this->emailValidator->validate('john@doe.shouldneverexist', true);
    }

    public function testIntegrationInvalidatesEmail()
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new EmailValidationSubscriber());

        $emailValidator = new EmailValidator($this->translator, $dispatcher);

        $this->expectException(InvalidEmailException::class);
        $this->expectExceptionMessage('bad email');

        $emailValidator->doPluginValidation('bad@gmail.com');
    }
}
