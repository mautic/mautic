<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Form\Validator\Constraints;

use Mautic\LeadBundle\Validator\Constraints\SafeUrl;
use Mautic\LeadBundle\Validator\Constraints\SafeUrlValidator;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class SafeUrlValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): ConstraintValidatorInterface
    {
        return new SafeUrlValidator();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('urlProvider')]
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
     * @return list<array{string, bool}>
     */
    public static function urlProvider(): array
    {
        return [
            ['http://example.com', true],
            ['https://example.com/path', true],
            ['data:text/html;base64,abc', false],
        ];
    }
}
