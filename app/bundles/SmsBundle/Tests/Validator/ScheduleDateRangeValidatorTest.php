<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Tests\Validator;

use Mautic\SmsBundle\Entity\Sms;
use Mautic\SmsBundle\Validator\ScheduleDateRange;
use Mautic\SmsBundle\Validator\ScheduleDateRangeValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

final class ScheduleDateRangeValidatorTest extends TestCase
{
    private ExecutionContextInterface&MockObject $context;

    private ScheduleDateRangeValidator $validator;

    protected function setUp(): void
    {
        $this->context   = $this->createMock(ExecutionContextInterface::class);
        $this->validator = new ScheduleDateRangeValidator();
        $this->validator->initialize($this->context);
    }

    public function testRejectsUnexpectedConstraintType(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate([], new NotBlank());
    }

    public function testEntityValidationIsSkippedWhenContinuingIsDisabled(): void
    {
        $sms = new Sms();
        $sms->setPublishUp(new \DateTime('2026-01-01 10:00:00'));
        $sms->setPublishDown(new \DateTime('2026-01-01 09:00:00'));
        $sms->setContinueSending(false);

        $this->context->expects($this->never())->method('buildViolation');

        $this->validator->validate($sms, new ScheduleDateRange());
    }

    #[DataProvider('validEntityRanges')]
    public function testValidEntityRanges(?\DateTimeInterface $publishUp, ?\DateTimeInterface $publishDown): void
    {
        $sms = new Sms();
        $sms->setSmsType('list');
        $sms->setContinueSending(true);
        $sms->setPublishUp($publishUp);
        $sms->setPublishDown($publishDown);

        $this->context->expects($this->never())->method('buildViolation');

        $this->validator->validate($sms, new ScheduleDateRange());
    }

    /**
     * @return iterable<string, array{\DateTimeInterface|null, \DateTimeInterface|null}>
     */
    public static function validEntityRanges(): iterable
    {
        yield 'missing start' => [null, new \DateTime('2026-01-01 11:00:00')];
        yield 'open-ended schedule' => [new \DateTime('2026-01-01 10:00:00'), null];
        yield 'stop after start' => [
            new \DateTime('2026-01-01 10:00:00'),
            new \DateTime('2026-01-01 11:00:00'),
        ];
    }

    #[DataProvider('invalidEntityRanges')]
    public function testInvalidEntityRanges(\DateTimeInterface $publishDown): void
    {
        $sms = new Sms();
        $sms->setSmsType('list');
        $sms->setContinueSending(true);
        $sms->setPublishUp(new \DateTime('2026-01-01 10:00:00'));
        $sms->setPublishDown($publishDown);

        $this->expectViolationAtPath('publishDown');

        $this->validator->validate($sms, new ScheduleDateRange());
    }

    /**
     * @return iterable<string, array{\DateTimeInterface}>
     */
    public static function invalidEntityRanges(): iterable
    {
        yield 'stop before start' => [new \DateTime('2026-01-01 09:00:00')];
        yield 'stop equals start' => [new \DateTime('2026-01-01 10:00:00')];
    }

    public function testFormValidationIsSkippedWhenContinuingIsDisabled(): void
    {
        $this->context->expects($this->never())->method('buildViolation');

        $this->validator->validate([
            'continueSending' => false,
            'publishUp'       => new \DateTime('2026-01-01 10:00:00'),
            'publishDown'     => new \DateTime('2026-01-01 09:00:00'),
        ], new ScheduleDateRange());
    }

    public function testInvalidFormRangeTargetsPublishDownField(): void
    {
        $this->expectViolationAtPath('[publishDown]');

        $this->validator->validate([
            'continueSending' => true,
            'publishUp'       => new \DateTime('2026-01-01 10:00:00'),
            'publishDown'     => new \DateTime('2026-01-01 09:00:00'),
        ], new ScheduleDateRange());
    }

    public function testUnsupportedValueIsIgnored(): void
    {
        $this->context->expects($this->never())->method('buildViolation');

        $this->validator->validate('not schedule data', new ScheduleDateRange());
    }

    private function expectViolationAtPath(string $path): void
    {
        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->expects($this->once())
            ->method('atPath')
            ->with($path)
            ->willReturnSelf();
        $violationBuilder->expects($this->once())->method('addViolation');

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with('mautic.form.date_time_range.invalid_range')
            ->willReturn($violationBuilder);
    }
}
