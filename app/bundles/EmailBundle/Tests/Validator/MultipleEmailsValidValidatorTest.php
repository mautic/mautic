<?php

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

class MultipleEmailsValidValidatorTest extends \PHPUnit_Framework_TestCase
{
    public function testNoEmailsProvided()
    {
        $emailValidatorMock = $this->getMockBuilder(EmailValidator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $constraintMock = $this->getMockBuilder(Constraint::class)
            ->disableOriginalConstructor()
            ->getMock();

        $emailValidatorMock->expects($this->never())
            ->method('validate');

        $multipleEmailsValidValidator = new MultipleEmailsValidValidator($emailValidatorMock);

        $multipleEmailsValidValidator->validate(null, $constraintMock);
    }

    public function testValidEmails()
    {
        $emailValidatorMock = $this->getMockBuilder(EmailValidator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $constraintMock = $this->getMockBuilder(Constraint::class)
            ->disableOriginalConstructor()
            ->getMock();

        $emailValidatorMock->expects($this->at(0))
            ->method('validate')
            ->with('john@don.com');

        $emailValidatorMock->expects($this->at(1))
            ->method('validate')
            ->with('don@john.com');

        $multipleEmailsValidValidator = new MultipleEmailsValidValidator($emailValidatorMock);

        $emails = 'john@don.com, don@john.com';
        $multipleEmailsValidValidator->validate($emails, $constraintMock);
    }

    public function testNotValidEmails()
    {
        $emailValidatorMock = $this->getMockBuilder(EmailValidator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $constraintMock = $this->getMockBuilder(Constraint::class)
            ->disableOriginalConstructor()
            ->getMock();

        $executionContextInterfaceMock = $this->getMockBuilder(ExecutionContextInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $constraintViolationBuilderInterfaceMock = $this->getMockBuilder(ConstraintViolationBuilderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $emailValidatorMock->expects($this->at(0))
            ->method('validate')
            ->with('john@don.com');

        $emailValidatorMock->expects($this->at(1))
            ->method('validate')
            ->with('xxx')
            ->willThrowException(new InvalidEmailException('xxx'));

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
