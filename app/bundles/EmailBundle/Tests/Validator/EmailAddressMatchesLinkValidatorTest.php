<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Validator;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\EmailBundle\Helper\EmailAddressLinkMatcher;
use Mautic\EmailBundle\Helper\MailHashHelper;
use Mautic\EmailBundle\Validator\EmailAddressMatchesLink;
use Mautic\EmailBundle\Validator\EmailAddressMatchesLinkValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

final class EmailAddressMatchesLinkValidatorTest extends TestCase
{
    private function createMailHashHelper(string $secret = 'secret'): MailHashHelper
    {
        $coreParameters = $this->createMock(CoreParametersHelper::class);
        $coreParameters->method('get')
            ->with('secret_key')
            ->willReturn($secret);

        return new MailHashHelper($coreParameters);
    }

    private function createEmailAddressLinkMatcher(string $secret = 'secret'): EmailAddressLinkMatcher
    {
        return new EmailAddressLinkMatcher($this->createMailHashHelper($secret));
    }

    public function testItSkipsEmptyValue(): void
    {
        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->never())
            ->method('buildViolation');

        $validator = new EmailAddressMatchesLinkValidator($this->createEmailAddressLinkMatcher());
        $validator->initialize($context);
        $validator->validate('', new EmailAddressMatchesLink(['secretHash' => 'hash']));
    }

    public function testItAddsViolationWhenHashDoesNotMatch(): void
    {
        $expectedHash = MailHashHelper::getEmailHashForSecret('john@doe.com', 'secret');

        $context   = $this->createMock(ExecutionContextInterface::class);
        $violation = $this->createMock(ConstraintViolationBuilderInterface::class);

        $context->expects($this->once())
            ->method('buildViolation')
            ->with('mautic.email.address.does.not.match.link')
            ->willReturn($violation);

        $violation->expects($this->once())
            ->method('addViolation');

        $validator = new EmailAddressMatchesLinkValidator($this->createEmailAddressLinkMatcher());
        $validator->initialize($context);
        $validator->validate('john@doe.com', new EmailAddressMatchesLink(['secretHash' => $expectedHash.'-different']));
    }

    public function testItAddsViolationWhenStatEmailDoesNotMatch(): void
    {
        $secretHash = MailHashHelper::getEmailHashForSecret('john@doe.com', 'secret');

        $context   = $this->createMock(ExecutionContextInterface::class);
        $violation = $this->createMock(ConstraintViolationBuilderInterface::class);

        $context->expects($this->once())
            ->method('buildViolation')
            ->with('mautic.email.address.does.not.match.link')
            ->willReturn($violation);

        $violation->expects($this->once())
            ->method('addViolation');

        $validator = new EmailAddressMatchesLinkValidator($this->createEmailAddressLinkMatcher());
        $validator->initialize($context);
        $validator->validate(
            'john@doe.com',
            new EmailAddressMatchesLink([
                'secretHash'       => $secretHash,
                'statEmailAddress' => 'jane@doe.com',
            ])
        );
    }

    public function testItPassesWhenHashAndStatEmailMatch(): void
    {
        $secretHash = MailHashHelper::getEmailHashForSecret('john@doe.com', 'secret');

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->never())
            ->method('buildViolation');

        $validator = new EmailAddressMatchesLinkValidator($this->createEmailAddressLinkMatcher());
        $validator->initialize($context);
        $validator->validate(
            'john@doe.com',
            new EmailAddressMatchesLink([
                'secretHash'       => $secretHash,
                'statEmailAddress' => 'john@doe.com',
            ])
        );
    }

    public function testItPassesWhenSubmittedEmailDiffersByCaseFromStatEmail(): void
    {
        $mailHash = $this->createMailHashHelper();

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

        $validator = new EmailAddressMatchesLinkValidator($this->createEmailAddressLinkMatcher());
        $validator->initialize($context);
        $validator->validate($inputEmail, $constraint);
    }
}
