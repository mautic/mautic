<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Form\Validator\Constraints;

use Mautic\LeadBundle\Validator\Constraints\SafeUrl;
use Mautic\LeadBundle\Validator\Constraints\SafeUrlValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

final class SafeUrlValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): ConstraintValidatorInterface
    {
        return new SafeUrlValidator();
    }

    #[DataProvider('urlProvider')]
    public function testSafeUrlValidation(string $url, bool $isValid): void
    {
        $constraint = new SafeUrl();

        $this->validator->validate($url, $constraint);

        if ($isValid) {
            $this->assertNoViolation();
        } else {
            $this->buildViolation($constraint->dataProtocolMessage)->assertRaised();
        }
    }

    /**
     * @return \Iterator<int<0, max>, array{string, bool}>
     */
    public static function urlProvider(): \Iterator
    {
        yield ['http://example.com', true];
        yield ['https://example.com/path', true];
        yield ['data:text/html;base64,abc', false];
    }
}
