<?php

declare(strict_types=1);

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\Validator;

use Mautic\EmailBundle\Exception\InvalidEmailException;
use Mautic\EmailBundle\Helper\EmailValidator;
use Mautic\EmailBundle\Validator\MultipleEmailsValidValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class MultipleEmailsValidValidatorTest extends \PHPUnit\Framework\TestCase
{
    public function testNoEmailsProvided(): void
    {
        $emailValidatorMock = $this->createMock(EmailValidator::class);
        $constraintMock     = $this->createMock(Constraint::class);

        $emailValidatorMock->expects($this->never())
            ->method('validate');

        $multipleEmailsValidValidator = new MultipleEmailsValidValidator($emailValidatorMock);

        $multipleEmailsValidValidator->validate(null, $constraintMock);
    }

    public function testValidEmails(): void
    {
        $emailValidatorMock = $this->createMock(EmailValidator::class);
        $constraintMock     = $this->createMock(Constraint::class);

        $emailValidatorMock->expects($this->exactly(2))
            ->method('validate')
            ->withConsecutive(['john@don.com'], ['don@john.com']);

        $multipleEmailsValidValidator = new MultipleEmailsValidValidator($emailValidatorMock);

        $emails = 'john@don.com, don@john.com';
        $multipleEmailsValidValidator->validate($emails, $constraintMock);
    }

    public function testNotValidEmails(): void
    {
        $emailValidatorMock                      = $this->createMock(EmailValidator::class);
        $constraintMock                          = $this->createMock(Constraint::class);
        $executionContextInterfaceMock           = $this->createMock(ExecutionContextInterface::class);
        $constraintViolationBuilderInterfaceMock = $this->createMock(ConstraintViolationBuilderInterface::class);

        $emailValidatorMock->expects($this->exactly(2))
            ->method('validate')
            ->withConsecutive(['john@don.com'], ['xxx'])
            ->willReturn(null, $this->throwException(new InvalidEmailException('xxx')));

        $executionContextInterfaceMock->expects($this->once())
            ->method('buildViolation')
            ->willReturn($constraintViolationBuilderInterfaceMock);

        $constraintViolationBuilderInterfaceMock->expects($this->once())
            ->method('addViolation')
            ->with();

        $multipleEmailsValidValidator = new MultipleEmailsValidValidator($emailValidatorMock);
        $multipleEmailsValidValidator->initialize($executionContextInterfaceMock);

        $emails = 'john@don.com, xxx';
        $multipleEmailsValidValidator->validate($emails, $constraintMock);
    }
}
