<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\Tests\Unit\Validator\Constraints;

use Mautic\DynamicContentBundle\Validator\Constraints\NoNesting;
use Mautic\DynamicContentBundle\Validator\Constraints\NoNestingValidator;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class NoNestingValidatorTest extends TestCase
{
    private const TRANSLATED_MESSAGE = 'DWC tokens cannot be used within another DWC.';
    private NoNesting $constraint;
    private NoNestingValidator $validator;
    private ExecutionContextInterface $context;

    protected function setUp(): void
    {
        $this->constraint = new NoNesting();
        $this->validator  = new NoNestingValidator();
        $this->context    = $this->createContext();
        $this->context->setConstraint($this->constraint);
        $this->validator->initialize($this->context);
    }

    public function testValidateWithInvalidConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage(sprintf('Expected argument of type "%s"', NoNesting::class));
        $this->validator->validate('value', new NotBlank());
    }

    public function testValidateWithInvalidType(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "string", "stdClass" given');
        $this->validator->validate(new \stdClass(), $this->constraint);
    }

    public function testValidateWithNull(): void
    {
        $this->validator->validate(null, $this->constraint);
        Assert::assertCount(0, $this->context->getViolations(), 'No violation should be added for a null value.');
    }

    public function testValidateWithValidValue(): void
    {
        $this->validator->validate('Some valid value', $this->constraint);
        Assert::assertCount(0, $this->context->getViolations(), 'No violation should be added for a valid value.');
    }

    public function testValidateWithInvalidValue(): void
    {
        $this->validator->validate('Some invalid value {dwc=some}', $this->constraint);
        Assert::assertCount(1, $this->context->getViolations(), 'There should be one violation for an invalid value.');
        Assert::assertSame(self::TRANSLATED_MESSAGE, $this->context->getViolations()->get(0)->getMessage());
    }

    private function createContext(): ExecutionContextInterface
    {
        $locale     = 'en_US';
        $validator  = $this->createMock(ValidatorInterface::class);
        $translator = new Translator($locale);
        $translator->addLoader('array', new ArrayLoader());

        $translator->addResource('array', [
            'mautic.dynamicContent.no_nesting' => self::TRANSLATED_MESSAGE,
        ], $locale, 'validators');

        return new ExecutionContext($validator, null, $translator, 'validators');
    }
}
