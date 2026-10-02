<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Validator\Constraints;

use Mautic\CoreBundle\Test\AbstractMauticTestCase;
use Mautic\LeadBundle\Form\Validator\Constraints\EmailAddress;
use Mautic\LeadBundle\Form\Validator\Constraints\EmailAddressValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class EmailAddressValidatorTest extends AbstractMauticTestCase
{
    #[DataProvider('provider')]
    public function testValidate(?string $value, int $expectedViolationCount): void
    {
        /** @var EmailAddressValidator $emailAddressValidator */
        $emailAddressValidator = self::getContainer()->get(EmailAddressValidator::class);
        $this->assertInstanceOf(EmailAddressValidator::class, $emailAddressValidator);

        $translator = self::getContainer()->get(TranslatorInterface::class);
        $this->assertInstanceOf(TranslatorInterface::class, $translator);

        $context = new ExecutionContext($this->createStub(ValidatorInterface::class), null, $translator);

        $emailAddressValidator->initialize($context);
        $emailAddressValidator->validate($value, new EmailAddress());

        $this->assertCount($expectedViolationCount, $context->getViolations());
    }

    /**
     * @return iterable<mixed[]>
     */
    public static function Provider(): iterable
    {
        yield [null, 0];
        yield ['', 0];
        yield ['test@test.com', 0];
        yield ['testtest.com', 1];
        yield ['test@testcom', 1];
        yield ['test@test@.com', 1];
    }
}
