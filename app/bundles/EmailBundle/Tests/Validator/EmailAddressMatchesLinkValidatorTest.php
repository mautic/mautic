<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Validator;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\EmailBundle\Helper\MailHashHelper;
use Mautic\EmailBundle\Validator\EmailAddressMatchesLink;
use Mautic\EmailBundle\Validator\EmailAddressMatchesLinkValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

final class EmailAddressMatchesLinkValidatorTest extends TestCase
{
    public function testItSkipsEmptyValue(): void
    {
        $mailHash = $this->createMock(MailHashHelper::class);
        $mailHash->expects($this->never())
            ->method('getEmailHash');

        $validator = new EmailAddressMatchesLinkValidator($mailHash);
        $validator->validate('', new EmailAddressMatchesLink(['secretHash' => 'hash']));
    }

    public function testItAddsViolationWhenHashDoesNotMatch(): void
    {
        $mailHash = $this->createMock(MailHashHelper::class);
        $mailHash->expects($this->once())
            ->method('getEmailHash')
            ->with('john@doe.com')
            ->willReturn('different-hash');

        $context   = $this->createMock(ExecutionContextInterface::class);
        $violation = $this->createMock(ConstraintViolationBuilderInterface::class);

        $context->expects($this->once())
            ->method('buildViolation')
            ->with('mautic.email.address.does.not.match.link')
            ->willReturn($violation);

        $violation->expects($this->once())
            ->method('addViolation');

        $validator = new EmailAddressMatchesLinkValidator($mailHash);
        $validator->initialize($context);
        $validator->validate('john@doe.com', new EmailAddressMatchesLink(['secretHash' => 'expected-hash']));
    }

    public function testItAddsViolationWhenStatEmailDoesNotMatch(): void
    {
        $mailHash = $this->createMock(MailHashHelper::class);
        $mailHash->expects($this->once())
            ->method('getEmailHash')
            ->with('john@doe.com')
            ->willReturn('expected-hash');

        $context   = $this->createMock(ExecutionContextInterface::class);
        $violation = $this->createMock(ConstraintViolationBuilderInterface::class);

        $context->expects($this->once())
            ->method('buildViolation')
            ->with('mautic.email.address.does.not.match.link')
            ->willReturn($violation);

        $violation->expects($this->once())
            ->method('addViolation');

        $validator = new EmailAddressMatchesLinkValidator($mailHash);
        $validator->initialize($context);
        $validator->validate(
            'john@doe.com',
            new EmailAddressMatchesLink([
                'secretHash'       => 'expected-hash',
                'statEmailAddress' => 'jane@doe.com',
            ])
        );
    }

    public function testItPassesWhenHashAndStatEmailMatch(): void
    {
        $mailHash = $this->createMock(MailHashHelper::class);
        $mailHash->expects($this->once())
            ->method('getEmailHash')
            ->with('john@doe.com')
            ->willReturn('expected-hash');

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->never())
            ->method('buildViolation');

        $validator = new EmailAddressMatchesLinkValidator($mailHash);
        $validator->initialize($context);
        $validator->validate(
            'john@doe.com',
            new EmailAddressMatchesLink([
                'secretHash'       => 'expected-hash',
                'statEmailAddress' => 'john@doe.com',
            ])
        );
    }

    public function testItPassesWhenSubmittedEmailDiffersByCaseFromStatEmail(): void
    {
        $coreParameters = $this->createMock(CoreParametersHelper::class);
        $coreParameters->method('get')
            ->with('secret_key')
            ->willReturn('secret');

        $mailHash = new MailHashHelper($coreParameters);

        $statEmail   = 'jan.linhart+Contact2@acquia.com';
        $secretHash  = $mailHash->getEmailHash($statEmail);
        $inputEmail  = 'jan.linhart+contact2@acquia.com';
        $constraint  = new EmailAddressMatchesLink([
            'secretHash'       => $secretHash,
            'statEmailAddress' => $statEmail,
        ]);

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->never())
            ->method('buildViolation');

        $validator = new EmailAddressMatchesLinkValidator($mailHash);
        $validator->initialize($context);
        $validator->validate($inputEmail, $constraint);
    }
}
