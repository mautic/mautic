<?php

namespace Mautic\LeadBundle\Tests\Form\Validator\Constraints;

use Mautic\LeadBundle\Form\Validator\Constraints\UrlImage;
use Mautic\LeadBundle\Form\Validator\Constraints\UrlImageValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class UrlImageValidatorTest extends TestCase
{
    public function testSkipsWhenNullOrNonString(): void
    {
        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->never())->method('buildViolation');

        $validator = new UrlImageValidator();
        $validator->initialize($context);

        $constraint = new UrlImage();

        $validator->validate(null, $constraint);
        $validator->validate(123, $constraint);
        $validator->validate([], $constraint);
    }

    public function testInvalidExtensionAddsViolationOnce(): void
    {
        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $builder->expects($this->once())->method('addViolation');

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->once())
            ->method('buildViolation')
            ->with('mautic.lead.field.companylogourl.invalid')
            ->willReturn($builder);

        $validator = new UrlImageValidator();
        $validator->initialize($context);

        $constraint = new UrlImage();

        // Use .invalid TLD so get_headers() won’t return Content-Type -> prevents extra violations
        $validator->validate('https://nonexistent-domain.invalid/logo.gif', $constraint);
    }

    public function testAllowedExtensionWithoutHeadersDoesNotAddViolation(): void
    {
        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $builder->expects($this->once())->method('addViolation');

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->once())
            ->method('buildViolation')
            ->with('mautic.lead.field.companylogourl.invalid')
            ->willReturn($builder);

        $validator = new UrlImageValidator();
        $validator->initialize($context);

        $constraint = new UrlImage();

        // .invalid TLD ensures get_headers() fails; allowed extension passes without header-based check
        $validator->validate('https://nonexistent-domain.invalid/image.png', $constraint);
    }

    public function testCaseInsensitiveExtensionAllowed(): void
    {
        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $builder->expects($this->once())->method('addViolation');

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->once())
            ->method('buildViolation')
            ->with('mautic.lead.field.companylogourl.invalid')
            ->willReturn($builder);

        $validator = new UrlImageValidator();
        $validator->initialize($context);

        $constraint = new UrlImage();

        $validator->validate('https://nonexistent-domain.invalid/logo.JPEG', $constraint);
    }
}
