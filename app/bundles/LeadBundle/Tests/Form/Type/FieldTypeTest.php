<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Form\Type;

use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Form\Type\FieldType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilder;

final class FieldTypeTest extends TestCase
{
    private MockObject $executionContext;

    private MockObject $formInterface;

    private MockObject $constraintViolationBuilder;

    protected function setUp(): void
    {
        $this->executionContext           = $this->createMock(ExecutionContextInterface::class);
        $this->formInterface              = $this->createMock(FormInterface::class);
        $this->constraintViolationBuilder = $this->createMock(ConstraintViolationBuilder::class);
    }

    public function testThatItFailsValidationIfTheDefaultValueExceedsTheFieldLengthLimit(): void
    {
        $this->executionContext->expects($this->once())
            ->method('getRoot')
            ->willReturn($this->formInterface);

        $leadField = new LeadField();
        $this->formInterface->expects($this->once())
            ->method('getViewData')
            ->willReturn($leadField);

        $limit = 100;
        $leadField->setCharLengthLimit($limit);

        $this->executionContext->expects($this->once())
            ->method('buildViolation')
            ->willReturn($this->constraintViolationBuilder);

        $value = str_repeat('a', $limit + 10);

        $this->constraintViolationBuilder->expects($this->once())
            ->method('addViolation');

        FieldType::validateDefaultValue($value, $this->executionContext);
    }

    public function testThatItPassesValidationIfTheFieldHasCorrectLength(): void
    {
        $this->executionContext->expects($this->exactly(2))
            ->method('getRoot')
            ->willReturn($this->formInterface);

        $leadField = new LeadField();
        $this->formInterface->expects($this->exactly(2))
            ->method('getViewData')
            ->willReturn($leadField);

        $limit = 100;
        $leadField->setCharLengthLimit($limit);

        $this->executionContext->expects($this->never())
            ->method('buildViolation')
            ->willReturn($this->constraintViolationBuilder);

        $value = str_repeat('a', $limit);

        $this->constraintViolationBuilder->expects($this->never())
            ->method('addViolation');

        FieldType::validateDefaultValue($value, $this->executionContext);

        $value = str_repeat('a', $limit - 1);
        FieldType::validateDefaultValue($value, $this->executionContext);
    }

    public function testThatItDoesntValidateLengthForHtmlFields(): void
    {
        $this->executionContext->expects($this->once())
            ->method('getRoot')
            ->willReturn($this->formInterface);

        $leadField = new LeadField();
        $leadField->setType('html');
        $this->formInterface->expects($this->once())
            ->method('getViewData')
            ->willReturn($leadField);

        $this->executionContext->expects($this->never())
            ->method('buildViolation')
            ->willReturn($this->constraintViolationBuilder);

        $value = str_repeat('a', 1000000);

        $this->constraintViolationBuilder->expects($this->never())
            ->method('addViolation');

        FieldType::validateDefaultValue($value, $this->executionContext);
    }

    public function testThatItDoesntValidateLengthForTextareaFields(): void
    {
        $this->executionContext->expects($this->once())
            ->method('getRoot')
            ->willReturn($this->formInterface);

        $leadField = new LeadField();
        $leadField->setType('textarea');
        $this->formInterface->expects($this->once())
            ->method('getViewData')
            ->willReturn($leadField);

        $this->executionContext->expects($this->never())
            ->method('buildViolation')
            ->willReturn($this->constraintViolationBuilder);

        $value = str_repeat('a', 1000000);

        $this->constraintViolationBuilder->expects($this->never())
            ->method('addViolation');

        FieldType::validateDefaultValue($value, $this->executionContext);
    }
}
