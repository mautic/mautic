<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Validator\Constraints;

use Mautic\CoreBundle\Test\AbstractMauticTestCase;
use Mautic\LeadBundle\Form\Validator\Constraints\EmailAddress;
use Mautic\LeadBundle\Form\Validator\Constraints\EmailAddressValidator;
use PHPUnit\Framework\Assert;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class EmailAddressValidatorTest extends AbstractMauticTestCase
{
    /**
     * @dataProvider provider
     */
    public function testValidate(?string $value, int $expectedViolationCount): void
    {
        /** @var EmailAddressValidator $emailAddressValidator */
        $emailAddressValidator = self::$container->get('mautic.validator.emailaddress');

        $context = new ExecutionContext(
            $this->createMock(ValidatorInterface::class),
            null,
            $this->createMock(TranslatorInterface::class)
        );

        $emailAddressValidator->initialize($context);
        $emailAddressValidator->validate($value, new EmailAddress());

        Assert::assertSame($expectedViolationCount, $context->getViolations()->count());
    }

    /**
     * @return iterable<mixed[]>
     */
    public function provider(): iterable
    {
        yield [null, 0];
        yield ['', 0];
        yield ['test@test.com', 0];
        yield ['testtest.com', 1];
        yield ['test@testcom', 1];
        yield ['test@test@.com', 1];
    }
}
