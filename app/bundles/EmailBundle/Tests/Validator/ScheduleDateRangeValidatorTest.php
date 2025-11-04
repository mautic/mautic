<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Validator;

use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Validator\ScheduleDateRange;
use Mautic\EmailBundle\Validator\ScheduleDateRangeValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class ScheduleDateRangeValidatorTest extends TestCase
{
    private ExecutionContextInterface|\PHPUnit\Framework\MockObject\MockObject $context;
    private ScheduleDateRangeValidator $validator;

    protected function setUp(): void
    {
        $this->context   = $this->createMock(ExecutionContextInterface::class);
        $this->validator = new ScheduleDateRangeValidator();
        $this->validator->initialize($this->context);
    }

    public function testValidatePassesWhenContinueSendingIsFalse(): void
    {
        $email = new Email();
        $email->setContinueSending(false);
        $email->setPublishUp(new \DateTime('2024-01-01 10:00:00'));
        $email->setPublishDown(new \DateTime('2024-01-01 09:00:00')); // Earlier than publishUp

        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate($email, new ScheduleDateRange());
    }

    public function testValidatePassesWhenPublishUpIsNull(): void
    {
        $email = new Email();
        $email->setContinueSending(true);
        $email->setPublishUp(null);
        $email->setPublishDown(new \DateTime('2024-01-01 09:00:00'));

        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate($email, new ScheduleDateRange());
    }

    public function testValidatePassesWhenPublishDownIsNull(): void
    {
        $email = new Email();
        $email->setContinueSending(true);
        $email->setPublishUp(new \DateTime('2024-01-01 10:00:00'));
        $email->setPublishDown(null);

        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate($email, new ScheduleDateRange());
    }

    public function testValidatePassesWhenPublishDownIsAfterPublishUp(): void
    {
        $email = new Email();
        $email->setContinueSending(true);
        $email->setPublishUp(new \DateTime('2024-01-01 10:00:00'));
        $email->setPublishDown(new \DateTime('2024-01-01 11:00:00')); // Later than publishUp

        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate($email, new ScheduleDateRange());
    }

    public function testValidateFailsWhenPublishDownIsBeforeOrEqualToPublishUp(): void
    {
        $email = new Email();
        $email->setContinueSending(true);
        $email->setPublishUp(new \DateTime('2024-01-01 10:00:00'));
        $email->setPublishDown(new \DateTime('2024-01-01 10:00:00')); // Same as publishUp

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->expects($this->once())
            ->method('atPath')
            ->with('publishDown')
            ->willReturnSelf();
        $violationBuilder->expects($this->once())
            ->method('addViolation');

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with('mautic.form.date_time_range.invalid_range')
            ->willReturn($violationBuilder);

        $this->validator->validate($email, new ScheduleDateRange());
    }

    public function testValidateFailsWhenPublishDownIsBeforePublishUp(): void
    {
        $email = new Email();
        $email->setContinueSending(true);
        $email->setPublishUp(new \DateTime('2024-01-01 10:00:00'));
        $email->setPublishDown(new \DateTime('2024-01-01 09:00:00')); // Earlier than publishUp

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->expects($this->once())
            ->method('atPath')
            ->with('publishDown')
            ->willReturnSelf();
        $violationBuilder->expects($this->once())
            ->method('addViolation');

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with('mautic.form.date_time_range.invalid_range')
            ->willReturn($violationBuilder);

        $this->validator->validate($email, new ScheduleDateRange());
    }

    public function testValidateFormDataPassesWhenContinueSendingIsFalse(): void
    {
        $formData = [
            'continueSending' => false,
            'publishUp'       => new \DateTime('2024-01-01 10:00:00'),
            'publishDown'     => new \DateTime('2024-01-01 09:00:00'),
        ];

        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate($formData, new ScheduleDateRange());
    }

    public function testValidateFormDataFailsWhenPublishDownIsBeforePublishUp(): void
    {
        $formData = [
            'continueSending' => true,
            'publishUp'       => new \DateTime('2024-01-01 10:00:00'),
            'publishDown'     => new \DateTime('2024-01-01 09:00:00'),
        ];

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->expects($this->once())
            ->method('atPath')
            ->with('[publishDown]')
            ->willReturnSelf();
        $violationBuilder->expects($this->once())
            ->method('addViolation');

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with('mautic.form.date_time_range.invalid_range')
            ->willReturn($violationBuilder);

        $this->validator->validate($formData, new ScheduleDateRange());
    }
}
