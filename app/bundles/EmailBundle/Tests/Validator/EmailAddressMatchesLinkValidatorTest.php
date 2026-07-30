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
    private function createMailHashHelper(string $secret = 'secret'): MailHashHelper
    {
        $coreParameters = $this->createMock(CoreParametersHelper::class);
        $coreParameters->method('get')
            ->with('secret_key')
            ->willReturn($secret);

        return new MailHashHelper($coreParameters);
    }

    public function testItSkipsEmptyValue(): void
    {
        $mailHash = $this->createMailHashHelper();
        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->never())
            ->method('buildViolation');

        $validator = new EmailAddressMatchesLinkValidator($mailHash);
        $validator->initialize($context);
        $validator->validate('', new EmailAddressMatchesLink(['secretHash' => 'hash']));
    }

    public function testItAddsViolationWhenHashDoesNotMatch(): void
    {
        $mailHash = $this->createMailHashHelper();
        $expectedHash = MailHashHelper::getEmailHashForSecret('john@doe.com', 'secret');

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
        $validator->validate('john@doe.com', new EmailAddressMatchesLink(['secretHash' => $expectedHash.'-different']));
    }

    public function testItAddsViolationWhenStatEmailDoesNotMatch(): void
    {
        $mailHash = $this->createMailHashHelper();
        $secretHash = MailHashHelper::getEmailHashForSecret('john@doe.com', 'secret');

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
                'secretHash'       => $secretHash,
                'statEmailAddress' => 'jane@doe.com',
            ])
        );
    }

    public function testItPassesWhenHashAndStatEmailMatch(): void
    {
        $mailHash = $this->createMailHashHelper();
        $secretHash = MailHashHelper::getEmailHashForSecret('john@doe.com', 'secret');

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->never())
            ->method('buildViolation');

        $validator = new EmailAddressMatchesLinkValidator($mailHash);
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

        $validator = new EmailAddressMatchesLinkValidator($mailHash);
        $validator->initialize($context);
        $validator->validate($inputEmail, $constraint);
    }
}
